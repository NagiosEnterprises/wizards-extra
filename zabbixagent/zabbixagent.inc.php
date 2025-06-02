<?php

include_once(dirname(__FILE__).'/../configwizardhelper.inc.php');
include_once __DIR__.'/../../../utils-xi2024-wizards.inc.php';

zabbixagent_configwizard_init();

function zabbixagent_configwizard_init(){
    //name / ID for config wizard 
    $name="zabbixagent";
    
    //relevant info for wizard  
    $args=array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => "This is a developer demo wizard that received data from a Zabbix Agent.", 
        CONFIGWIZARD_DISPLAYTITLE => "Zabbix Agent",
        CONFIGWIZARD_FUNCTION => "zabbixagent_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "zabbix.png",
        CONFIGWIZARD_VERSION => "1.0",
        CONFIGWIZARD_DATE => "2025-02-05",
        CONFIGWIZARD_COPYRIGHT => "Copyright &copy; 2008-2010 Nagios Enterprises, LLC.",
        CONFIGWIZARD_AUTHOR => "Nagios Enterprises, LLC",
        CONFIGWIZARD_REQUIRES_VERSION => 60100
    );
    //register wizard with XI     
    register_configwizard($name,$args);
}

/**
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function zabbixagent_configwizard_func($mode="",$inargs=null,&$outargs,&$result){

    $wizard_name="zabbixagent";

    // initialize return code and output
    $result=0;
    $output="";
    
    // initialize output args - pass back the same data we got -> used by XI framework, don't change
    $outargs[CONFIGWIZARD_PASSBACK_DATA]=$inargs;
    
    //main wizard stage switch     
    switch($mode){
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:
            
            $address = grab_array_var($inargs, "ip_address", "");
            $nodes = get_configwizard_hosts($wizard_name);

            ob_start();
            include __DIR__.'/steps/step1.php';
            $output = ob_get_clean();
            
            break;
        
        //FORM VALIDATION FOR STAGE 1 
        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:        
        
            $address = grab_array_var($inargs, "ip_address", "");
                        
            $errors=0;
            $errmsg=array();
            if(have_value($address)==false){
                $errmsg[$errors++]="No host address specified.";
             }
            
                
            if($errors>0){
                print_r($errmsg);
                print("Address: " . $address);
                $result=1;
                }
            //proceed to next stage if there are no errors, or show stage 1 if there are errors     
            break;
            
        case CONFIGWIZARD_MODE_GETSTAGE2HTML:
            //get variables that were passed to us 
            $address = grab_array_var($inargs, "ip_address");
            $ha = @gethostbyaddr($address);

            if ($ha == "") {
                $ha = $address;
            }

            $hostname = grab_array_var($inargs, "hostname", $ha);
            $api_url = grab_array_var($inargs, "api_url", "http://{$address}/zabbix/api_jsonrpc.php");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            if ($services_serial != "") {
                $services = json_decode(base64_decode($services_serial), true);
            }
            if ($serviceargs_serial != "") {
                $serviceargs = json_decode(base64_decode($serviceargs_serial), true);
            }


            ob_start();
            include __DIR__.'/steps/step2.php';
            $output = ob_get_clean();
            
            break;
        
        //form validation stage 2 
        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:
            
            // get variables that were passed to us
            $address = grab_array_var($inargs, "ip_address");
            $hostname = grab_array_var($inargs, "hostname");
            $api_url = grab_array_var($inargs, "api_url");
            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());
            
            // check for errors
            $errors=0;
            $errmsg=array();
            if(have_value($address)==false){
                $errmsg[$errors++]="No host address specified.";
            }else{
                
                    if(!filter_var($address,FILTER_VALIDATE_IP)){
                        $errmsg[$errors++]="Invalid Host address. Must enter a valid URL or IP address";
                    }
                
            }
            if (is_valid_host_name($hostname) == false) {
                $errmsg[$errors++] = _("Invalid host name.");
            }
            if (have_value($api_url) == false) {
                $errmsg[$errors++] = _("No API URL specified.");
            } else if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
                $errmsg[$errors++] = _("Invalid API URL.");
            }

            $required_services = [
                "cpu" => ["warning" => "Warning threshold for CPU is required.", "critical" => "Critical threshold for CPU is required."],
                "memory" => ["warning" => "Warning threshold for Memory is required.", "critical" => "Critical threshold for Memory is required."],
                "disk" => ["warning" => "Warning threshold for Disk is required.", "critical" => "Critical threshold for Disk is required."],
                "net_in" => ["warning" => "Warning threshold for Network In is required.", "critical" => "Critical threshold for Network In is required."],
                "net_out" => ["warning" => "Warning threshold for Network Out is required.", "critical" => "Critical threshold for Network Out is required."],
                "process_count" => ["warning" => "Warning threshold for Process Count is required.", "critical" => "Critical threshold for Process Count is required."],
                "cpu_load" => ["warning" => "Warning threshold for CPU Load is required.", "critical" => "Critical threshold for CPU Load is required."],
                "hostname" => ["warning" => "Warning threshold for Hostname is required.", "critical" => "Critical threshold for Hostname is required."]
            ];

            // Add debug output
            error_log("Selected services: " . print_r($services, true));
            error_log("Service arguments: " . print_r($serviceargs, true));

            foreach ($required_services as $service => $thresholds) {
                // Only validate if service is selected and is "on"
                if (isset($services[$service]) && $services[$service] === 'on') {
                    // Skip threshold validation for uptime
                    if ($service !== 'uptime') {
                        foreach ($thresholds as $threshold => $message) {
                            if (!isset($serviceargs[$service][$threshold]) || 
                                $serviceargs[$service][$threshold] === '' || 
                                $serviceargs[$service][$threshold] === null) {
                                $errmsg[$errors++] = _($message);
                                error_log("Validation error for $service $threshold");
                            }
                        }
                    }
                }
            }

            if ($errors > 0) {
                error_log("Total validation errors: $errors");
                error_log("Error messages: " . print_r($errmsg, true));
                $result = 1;
            } else {
                // Create a clean services array with only selected services
                $selected_services = array();
                foreach ($services as $service => $state) {
                    if ($state === 'on') {
                        $selected_services[$service] = $state;
                    }
                }
                
                $outargs[CONFIGWIZARD_PASSBACK_DATA] = array(
                    "hostname" => $hostname,
                    "ip_address" => $address,
                    "api_url" => $api_url,
                    "services" => $selected_services,
                    "serviceargs" => $serviceargs
                );
                
                // Properly encode all selected services
                $outargs["services_serial"] = base64_encode(json_encode($selected_services));
                $outargs["serviceargs_serial"] = base64_encode(json_encode($serviceargs));
            }
            
                
            break;
            
        case CONFIGWIZARD_MODE_GETSTAGE3HTML:
        
            $address = grab_array_var($inargs, "ip_address");
            $hostname = grab_array_var($inargs, "hostname");
            $api_url = grab_array_var($inargs, "api_url");
            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());
            $services_serial = (!empty($services) ? base64_encode(json_encode($services)) : grab_array_var($inargs, "services_serial", ''));
            $serviceargs_serial = (!empty($serviceargs) ? base64_encode(json_encode($serviceargs)) : grab_array_var($inargs, "serviceargs_serial", ''));
          
            $services = json_decode(base64_decode($services_serial), true);
            $serviceargs = json_decode(base64_decode($serviceargs_serial), true);

           

            $output = '
            <input type="hidden" name="ip_address" value="' . encode_form_val($address) . '">
            <input type="hidden" name="hostname" value="' . encode_form_val($hostname) . '">
            <input type="hidden" name="api_url" value="' . encode_form_val($api_url) . '">
            <input type="hidden" name="services_serial" value="' . encode_form_val($services_serial) . '">
            <input type="hidden" name="serviceargs_serial" value="' . encode_form_val($serviceargs_serial) . '">
            ';
        
            break;
            
        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:
            break;
                    
        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:
            $output = '';
            break;
        
        //commit the wizard data into objects definitions to be imported 
        case CONFIGWIZARD_MODE_GETOBJECTS:
        
            //get the session data to turn into object configs 
            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "ip_address", "");
            $api_url = grab_array_var($inargs, "api_url", "");
      
        
            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            

            $services = json_decode(base64_decode($services_serial), true);
            $serviceargs = json_decode(base64_decode($serviceargs_serial), true);

      
            // Sanitize the data
            foreach ($services as $key => $value) {
                $services[$key] = filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            }
            foreach ($serviceargs as $key => $args) {
                foreach ($args as $arg_key => $arg_value) {
                    $serviceargs[$key][$arg_key] = filter_var($arg_value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                }
            }

        
           
            
            //initialize objects array 
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["ip_address"] = $address;
            $meta_arr["api_url"] = $api_url;
            $meta_arr["services"] = $services;
            $meta_arr["serviceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();
            //make sure it's not a duplicate hostname 
            if(!host_exists($hostname))
            {
                $objs[]=array(
                    "type"          => OBJECTTYPE_HOST,
                    "use"           => "xiwizard_generic_host",
                    "host_name"     => $hostname,
                    "address"       => $address,
                    "icon_image"    => "zabbix.png",
                    "statusmap_image" => "zabbix.png",
                    "_xiwizard"     => $wizard_name,
                );
            }


            foreach ($services as $service => $state) {
                if ($state === 'on') {
                    $check_command = "check_zabbix_agent_plugin!-H {$address} !--check {$service} !--warning {$serviceargs[$service]['warning']} !--critical {$serviceargs[$service]['critical']} !--api-url {$api_url}";
                    $objs[] = array(
                        "type" => OBJECTTYPE_SERVICE,
                        "host_name" => $hostname,
                        "service_description" => ucfirst(str_replace("_", " ", $service)),
                        "use" => "xiwizard_generic_service",
                        "check_command" => $check_command,
                        "_xiwizard" => $wizard_name,
                    );
                }
            }

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;
        
            break;
            
        default:
            break;            
        }
        
    return $output;
}
?>