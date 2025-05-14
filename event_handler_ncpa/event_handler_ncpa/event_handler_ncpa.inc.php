<?php
//
// Event Handler Config Wizard
// Copyright (c) 2025 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

event_handler_ncpa_configwizard_init();

function event_handler_ncpa_configwizard_init()
{
    $name = "event_handler_ncpa";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.0.1",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Add an event handler from a remote host via NCPA."),
        CONFIGWIZARD_DISPLAYTITLE => "Remote Event Handler",
        CONFIGWIZARD_FUNCTION => "event_handler_ncpa_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "event_handler_ncpa.png",
        CONFIGWIZARD_FILTER_GROUPS => array('network', 'server'),
        CONFIGWIZARD_REQUIRES_VERSION => 60100
    );
    register_configwizard($name, $args);
}

/**
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function event_handler_ncpa_configwizard_func($mode = "", $inargs = null, &$outargs = null, &$result = null)
{
    $wizard_name = "event_handler_ncpa";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $hostname = grab_array_var($inargs, "hostname", $ha);
            $service_name = grab_array_var($inargs, "service_name", $ha);

            # Get the existing host/node configurations.
            $nodes = get_configwizard_hosts($wizard_name);

            ########################################################################################
            # Load the html
            # - The html needs to end up in the $output string, so use ob_start() and ob_get_clean()
            #   to load the PHP from the Step1 file into the $output string.
            ########################################################################################
            ob_start();

// Stage 1 Host Overlay
            $hostxml = get_xml_host_objects();


            $display_host_display_name = grab_request_var("display_host_display_name", get_user_meta(0, "display_host_display_name"));
            $display_service_display_name = grab_request_var("display_service_display_name", get_user_meta(0, "display_service_display_name"));

            // Stage 1 Service Overlay
            $servicexml = get_xml_service_objects();
            $servicexml_min = array();
            foreach($servicexml as $key => $service) {
                $servicexml_min_out = array();
                foreach($service as $key => $value) {
                    $value = strval($value);

                    if($key == 'host_name') {
                        $servicexml_min_out['host_name'] = $value;
                    } else if($key == 'service_description' && !$display_service_display_name) {
                        $servicexml_min_out['name'] = $value;
                    } else if($key == 'display_name' && $display_service_display_name) {
                        $servicexml_min_out['name'] = $value;
                    }
                }
                $servicexml_min[] = $servicexml_min_out;
            }

            // hidden overlay divs
            $output .= construct_overlay("host", $hostxml);
            $output .= construct_overlay("service", $servicexml);
            
            include __DIR__.'/steps/step1.php';
            $output = ob_get_clean();

            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $service_name = grab_array_var($inargs, "service_name", $ha);

            // Check for errors
            $errors = 0;
            $errmsg = array();
            
            if (!host_exists($hostname)) {
                $errmsg[$errors++] = "Please select a valid host.";
            }
            if ($service_name == "") {
                $errmsg[$errors++] = "Please select a valid service.";
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "ip_address");

            $ha = @gethostbyaddr($address);

            if ($ha == "") {
                $ha = $address;
            }

            $hostname = grab_array_var($inargs, "hostname", $ha);
            $service_name = grab_array_var($inargs, "service_name", $ha);
            $address = grab_array_var($inargs, "ip_address", "");
            $event_handler = grab_array_var($inargs, "event_handler");
            $token = grab_array_var($inargs, "token");
            $arguments = grab_array_var($inargs, "arguments");
            $port = grab_array_var($inargs, "port", "5693");


            ########################################################################################
            # Load the html
            # - The html needs to end up in the $output string, so use ob_start() and ob_get_clean()
            #   to load the PHP from the Step2 file into the $output string.
            ########################################################################################
            ob_start();
            include __DIR__.'/steps/step2.php';
            $output = ob_get_clean();

            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "ip_address");
            $hostname = grab_array_var($inargs, "hostname");
            $service_name = grab_array_var($inargs, "service_name", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());

            $event_handler = grab_array_var($inargs, "event_handler");
            $token = grab_array_var($inargs, "token");
            $arguments = grab_array_var($inargs, "arguments");
            $port = grab_array_var($inargs, "port", "5693");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($event_handler) == false) {
                $errmsg[$errors++] = _("No event_handler specified.");
            }
            if (have_value($port) == false) {
                $errmsg[$errors++] = _("No port specified.");
            }
            if (have_value($address) == false) {
                $errmsg[$errors++] = _("No address specified.");
            }
            if (have_value($token) == false) {
                $errmsg[$errors++] = _("No token specified.");
            }

            // Test the connection if no errors
            if (empty($errors)) {
                $ip_address_replaced = nagiosccm_replace_user_macros($address);
                $port_replaced = nagiosccm_replace_user_macros($port);
                $token_replaced = nagiosccm_replace_user_macros($token);

                // The URL we will use to query the NCPA agent, and do a walk
                // of all monitorable items.
                $query_url = "https://{$address}:{$port}/testconnect/?token=".urlencode($token);
                $query_url_replaced = "https://{$ip_address_replaced}:{$port_replaced}/testconnect/?token=".urlencode($token_replaced);

                // Remove SSL verification or not
                $context = array("ssl" => array("verify_peer" => false, "verify_peer_name" => false));

                $no_ssl_verify = grab_array_var($inargs, "no_ssl_verify", false);
                if ($no_ssl_verify) {
                    $context['ssl']['verify_peer'] = false;
                    $context['ssl']['verify_peer_name'] = false;
                }

                // All we want to do is test if we can hit this URL.
                // Error Control Operator - @ - http://php.net/manual/en/language.operators.errorcontrol.php

                // For a simple request, make timeout shorter than php default or typical gateway timeout of 60
                ini_set("default_socket_timeout", 10);
                $raw_json = @file_get_contents($query_url_replaced, false, stream_context_create($context));

                # This will be displayed, so hide the token, for "security"
                $safe_url = str_replace('token='.urlencode($token_replaced), "token=&lt;your_token&gt;", $query_url);

                if ($raw_json === FALSE || empty($raw_json)) {
                    $errmsg[$errors++] = _("Unable to contact server at")." $safe_url";

                } else {
                    $json = json_decode($raw_json, true);

                    # This should not happen, but it might.
                    if (!is_array($json)) {
                        $errmsg[$errors++] = _("Bad data received")." $safe_url<br>"._('This may be a temporary condition, please check that NCPA is running and try resubmitting.');
                    } else if (!array_key_exists('value', $json)) {
                        $errmsg[$errors++] = _("Bad token for connection.");
                    }
                }
            }

                
            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "ip_address");
            $hostname = grab_array_var($inargs, "hostname");
            $service_name = grab_array_var($inargs, "service_name", $ha);

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");
            $event_handler = grab_array_var($inargs, "event_handler");
            $token = grab_array_var($inargs, "token");
            $arguments = grab_array_var($inargs, "arguments");
            $port = grab_array_var($inargs, "port", "5693");

            $output = '

        <input type="hidden" name="ip_address" value="' . encode_form_val($address) . '">
        <input type="hidden" name="hostname" value="' . encode_form_val($hostname) . '">
        <input type="hidden" name="service_name" value="' . encode_form_val($service_name) . '">
        <input type="hidden" name="event_handler" value="' . encode_form_val($event_handler) . '">
        <input type="hidden" name="token" value="' . encode_form_val($token) . '">
        <input type="hidden" name="arguments" value="' . encode_form_val($arguments) . '">
        <input type="hidden" name="port" value="' . encode_form_val($port) . '">
        <input type="hidden" name="services_serial" value="' . base64_encode(json_encode($services)) . '">
        <input type="hidden" name="serviceargs_serial" value="' . base64_encode(json_encode($serviceargs)) . '">
';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:
            $address = grab_array_var($inargs, "ip_address");
            $hostname = grab_array_var($inargs, "hostname");
            $service_name = grab_array_var($inargs, "service_name", $ha);

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");
            $event_handler = grab_array_var($inargs, "event_handler");
            $token = grab_array_var($inargs, "token");
            $arguments = grab_array_var($inargs, "arguments");
            $port = grab_array_var($inargs, "port", "5693");
            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:
            $address = grab_array_var($inargs, "ip_address");
            $hostname = grab_array_var($inargs, "hostname");
            $service_name = grab_array_var($inargs, "service_name", $ha);

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");
            $event_handler = grab_array_var($inargs, "event_handler");
            $token = grab_array_var($inargs, "token");
            $arguments = grab_array_var($inargs, "arguments");
            $port = grab_array_var($inargs, "port", "5693");

            $output = '
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "ip_address", "");
            $hostaddress = $address;
            $service_name = grab_array_var($inargs, "service_name", $ha);

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = json_decode(base64_decode($services_serial), true);
            $serviceargs = json_decode(base64_decode($serviceargs_serial), true);
            
            $event_handler = grab_array_var($inargs, "event_handler");
            $token = grab_array_var($inargs, "token");
            $arguments = grab_array_var($inargs, "arguments");
            $port = grab_array_var($inargs, "port", "5693");

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["ip_address"] = $address;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            $meta_arr["event_handler"] = $event_handler;
            $meta_arr["token"] = $token;
            $meta_arr["arguments"] = $arguments;
            $meta_arr["port"] = $port;
            $meta_arr["service_name"] = $service_name;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            
            //DEFINE COMMAND
            $command_name = $event_handler."_".uniqid();
            $objs = array();
            $objs[] = array(
                "type" => OBJECTTYPE_COMMAND,
                "command_name" => "$command_name",
                "command_line" => "/usr/local/nagios/libexec/check_ncpa.py -H " . $address . " -M 'plugins/" . $event_handler . "' -t '" . $token . "' -P '" . $port . "' -a '" . $arguments . "'",
            );

            if (true) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_event_handler_host",
                    "host_name" => $hostname,
                    "_xiwizard" => $wizard_name,
                );
            }

            $pluginopts = "";
            $objs[] = array(
                "type" => OBJECTTYPE_SERVICE,
                "host_name" => $hostname,
                "service_description" => $service_name,
                "use" => "xiwizard_event_handler_service",
                "event_handler" => "$command_name",
                "event_handler_enabled" => 1,
                "_xiwizard" => $wizard_name,
            );


            // Return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}
