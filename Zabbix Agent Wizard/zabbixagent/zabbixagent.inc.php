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
            if (!isset($_POST['backButton'])) {
                unset($_SESSION['zabbixagent_wizard_hostname']);
                unset($_SESSION['zabbixagent_wizard_ip_address']);
                unset($_SESSION['zabbixagent_wizard_services']);
                unset($_SESSION['zabbixagent_wizard_serviceargs']);
            }
            
            $address = grab_array_var($inargs, "ip_address", "");

            ob_start();
            include __DIR__.'/steps/step1.php';
            $output = ob_get_clean();
            
            break;
        
        //FORM VALIDATION FOR STAGE 1 
        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:        
        
            $address = grab_array_var($inargs, "ip_address", "");
            if (array_key_exists('zabbixagent_wizard_ip_address', $_SESSION) && $address == "") {
                $address = $_SESSION['zabbixagent_wizard_ip_address'];
            }
            $errors=0;
            $errmsg=array();
            if(have_value($address)==false){
                $errmsg[$errors++]="No host address specified.";
             }
             if($address){
                $_SESSION['zabbixagent_wizard_ip_address'] = $address;
             }

            if($errors>0){
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
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
            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            if ($services_serial != "") {
                $services = json_decode(base64_decode($services_serial), true);
            }
            if ($serviceargs_serial != "") {
                $serviceargs = json_decode(base64_decode($serviceargs_serial), true);
            }

             if (isset($_SESSION['zabbixagent_wizard_ip_address']) && $address == "") {
                $address = $_SESSION['zabbixagent_wizard_ip_address'];
            }
            if (isset($_SESSION['zabbixagent_wizard_hostname']) && $hostname == "") {
                $hostname = $_SESSION['zabbixagent_wizard_hostname'];
            }
            if (isset($_SESSION['zabbixagent_wizard_services']) && $services_serial == "") {
                $services_serial = base64_encode(json_encode($_SESSION['zabbixagent_wizard_services']));
            }
            if (isset($_SESSION['zabbixagent_wizard_serviceargs']) && $serviceargs_serial == "") {
                $serviceargs_serial = base64_encode(json_encode($_SESSION['zabbixagent_wizard_serviceargs']));
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
            // $api_url = grab_array_var($inargs, "api_url");
            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());

            // Repopulate from session if empty (for back navigation)
            if (empty($address) && isset($_SESSION['zabbixagent_wizard_ip_address'])) {
                $address = $_SESSION['zabbixagent_wizard_ip_address'];
            }
            if (empty($hostname) && isset($_SESSION['zabbixagent_wizard_hostname'])) {
                $hostname = $_SESSION['zabbixagent_wizard_hostname'];
            }
            if (empty($services) && isset($_SESSION['zabbixagent_wizard_services'])) {
                $services = $_SESSION['zabbixagent_wizard_services'];
            }
            if (empty($serviceargs) && isset($_SESSION['zabbixagent_wizard_serviceargs'])) {
                $serviceargs = $_SESSION['zabbixagent_wizard_serviceargs'];
            }

            if ($address) {
                $_SESSION['zabbixagent_wizard_ip_address'] = $address;
            }
            if ($hostname) {
                $_SESSION['zabbixagent_wizard_hostname'] = $hostname;
            }
            if ($services) {
                $_SESSION['zabbixagent_wizard_services'] = $services;
            }
            if ($serviceargs) {
                $_SESSION['zabbixagent_wizard_serviceargs'] = $serviceargs;
            }
            
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

            $required_services = [
                "cpu" => ["warning" => "Warning threshold for CPU is required.", "critical" => "Critical threshold for CPU is required."],
                "memory" => ["warning" => "Warning threshold for Memory is required.", "critical" => "Critical threshold for Memory is required."],
                "disk" => ["warning" => "Warning threshold for Disk is required.", "critical" => "Critical threshold for Disk is required."],
                "net_in" => ["warning" => "Warning threshold for Network In is required.", "critical" => "Critical threshold for Network In is required." , "interface" => "Interface for Network In is required."],
                "net_out" => ["warning" => "Warning threshold for Network Out is required.", "critical" => "Critical threshold for Network Out is required.", "interface" => "Interface for Network Out is required."],
                "process_count" => ["warning" => "Warning threshold for Process Count is required.", "critical" => "Critical threshold for Process Count is required."],
                "cpu_load" => ["warning" => "Warning threshold for CPU Load is required.", "critical" => "Critical threshold for CPU Load is required."],
                "hostname" => ["warning" => "Warning threshold for Hostname is required.", "critical" => "Critical threshold for Hostname is required."]
            ];

            // Add debug output
            error_log("Selected services: " . print_r($services, true));
            error_log("Service arguments: " . print_r($serviceargs, true));

            // Require at least one service to be selected
            $selected_services_count = 0;
            foreach ($services as $service => $state) {
                if ($state === 'on') {
                    $selected_services_count++;
                }
            }
            if ($selected_services_count === 0) {
                $errmsg[$errors++] = "You must select at least one service.";
            }

            // Only validate thresholds for selected services
            foreach ($required_services as $service => $thresholds) {
                if (isset($services[$service]) && $services[$service] === 'on') {
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

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
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
            // $api_url = grab_array_var($inargs, "api_url", "");
      
        
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
                    if (in_array($arg_key, ['warning', 'critical'])) {
                        $serviceargs[$key][$arg_key] = filter_var($arg_value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    } else {
                        // Just sanitize special characters for strings like interface name
                        $serviceargs[$key][$arg_key] = filter_var($arg_value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    }
                }
            }
           
            
            //initialize objects array 
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["ip_address"] = $address;
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
                    // Build check command based on step2.php fields and plugin support
                    $check_command = "check_zabbix_agent_plugin!-H {$address} !--check {$service}";

                    // Add thresholds for all metrics (as in step2.php, all have warning/critical)
                    $warning = isset($serviceargs[$service]['warning']) ? $serviceargs[$service]['warning'] : '';
                    $critical = isset($serviceargs[$service]['critical']) ? $serviceargs[$service]['critical'] : '';
                    $check_command .= " !--warning {$warning} !--critical {$critical}";

                    // Add --interface for net_in and net_out if interface is set
                    if (($service === 'net_in' || $service === 'net_out') && !empty($serviceargs[$service]['interface'])) {
                        $interface = isset($serviceargs[$service]['interface']) ? $serviceargs[$service]['interface'] : '';
                        // Wrap in quotes to preserve interface names like ens33
                        $check_command .= ' !--interface "' . $interface . '"';
                    }

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