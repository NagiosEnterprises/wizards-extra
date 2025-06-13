<!--                                   -->
    <!-- The initial data set from Step 1. -->
    <!--                                   -->
    <input type="hidden" id="hostname" name="hostname" value="<?= encode_form_val($hostname) ?>">
    <input type="hidden" id="operation" name="operation" value="<?= encode_form_val($operation) ?>">
    <input type="hidden" id="selectedhostconfig" name="selectedhostconfig" value="<?= encode_form_val($selectedhostconfig) ?>">
    <input type="hidden" id="config_serial" name="config_serial" value="<?= (!empty($config)) ? base64_encode(json_encode($config)) : "" ?>" />
    <input type="hidden" name="ip_address" value="<?= encode_form_val($address) ?>">
    <input type="hidden" name="services_serial" value="<?= encode_form_val($services_serial) ?>">
    <input type="hidden" name="serviceargs_serial" value="<?= encode_form_val($serviceargs_serial) ?>">
    <input type="hidden" name="aurora_serial" value="<?= encode_form_val($aurora_serial) ?>">
<?php
    #include_once __DIR__.'/../../../utils-xi2024-wizards.inc.php';
     $services = array();
    if (array_key_exists('zabbixagent_wizard_services', $_SESSION)) {
        $services = $_SESSION['zabbixagent_wizard_services'];
    }
?>
    <div class="container m-0 g-0">
        <h2 class="mb-2"><?= _('Host Details') ?></h2>

        <div class="row mb-2">
            <div class="col-sm-6">
                <label for="hostname" class="form-label form-item-required"><?= _('Host Name:') ?> <?= xi6_info_tooltip(_('Name you would like to associate with this host')) ?></label>
                <div class="input-group position-relative">
                    <input type="text" name="hostname" id="hostname" value="<?= encode_form_val($hostname) ?>" class="form-control form-control-sm monitor rounded" placeholder="<?= _("Enter Host Name:") ?>" >
                    <div class="invalid-feedback">
                        <?= _("Please enter the Host Name") ?>
                    </div>
                    <i id="hostname_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                </div>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-sm-6">
                <label for="ip_address" class="form-label form-item-required"><?= _('Host Address:') ?> <?= xi6_info_tooltip(_('IP address or FQDN of the host')) ?></label>
                <div class="input-group position-relative">
                    <input type="text" name="ip_address" id="ip_address" value="<?= encode_form_val($address) ?>" class="form-control form-control-sm monitor rounded" placeholder="<?= _("Enter Host Address:") ?>" >
                    <div class="invalid-feedback">
                        <?= _("Please enter the Host Address") ?>
                    </div>
                    <i id="ip_address_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                </div>
            </div>
        </div>
        
        <h2 class="mt-4"><?= _('Available Metrics') ?></h2>
        <p><?= _('Specify which metrics you would like monitor') ?></p>

        <div class="row">
            <div class="col-sm-12">
                <fieldset class="row g-2 mb-1 wz-fieldset align-items-center">
                    <div class="form-check col-sm-2 d-flex align-items-center">
                        <input type="checkbox" id="select_all_metrics" class="form-check-input me-2" onclick="selectAllInSection('metrics', this.checked)">
                        <label for="select_all_metrics" class="form-check-label bold me-2 text-nowrap"><?= _('Select All Metrics') ?></label>
                    </div>
                </fieldset>
            </div>
        </div>
        
        <div class="row">
            <div class="col-sm-12">
                <fieldset class="row g-2 mb-1 wz-fieldset align-items-center metrics">
                    <div class="form-check col-sm-2 d-flex align-items-center">
                        <input type="checkbox" id="cpu" class="form-check-input me-2" name="services[cpu]" <?= isset($services["cpu"]) && $services["cpu"] ? 'checked="checked"' : '' ?> onchange="updateSelectAll('metrics')">
                        <label for="cpu" class="form-check-label bold me-2 text-nowrap"><?= _('CPU') ?> <?= xi6_info_tooltip(_("Monitors the CPU usage")) ?></label>
                    </div>
                    <div class="col-sm-6 offset-sm-2">
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i <?= xi6_title_tooltip(_('Warning Threshold (default = 70)')) ?> class="material-symbols-outlined md-warning md-18 md-400">warning</i>
                                    </span>
                                    <input type="text" name="serviceargs[cpu][warning]" id="cpu_warning" value="<?= encode_form_val($serviceargs["cpu"]["warning"] ?? 70) ?>" class="form-control form-control-sm">
                                    <i id="services_cpu_warning_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i <?= xi6_title_tooltip(_('Critical Threshold (default = 90)')) ?> class="material-symbols-outlined md-critical md-18 md-400">error</i>
                                    </span>
                                    <input type="text" name="serviceargs[cpu][critical]" id="cpu_critical" value="<?= encode_form_val($serviceargs["cpu"]["critical"] ?? 90) ?>" class="form-control form-control-sm">
                                    <i id="services_cpu_critical_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <fieldset class="row g-2 mb-1 wz-fieldset align-items-center metrics">
                    <div class="form-check col-sm-2 d-flex align-items-center">
                        <input type="checkbox" id="memory" class="form-check-input me-2" name="services[memory]" <?= isset($services["memory"]) && $services["memory"] ? 'checked="checked"' : '' ?> onchange="updateSelectAll('metrics')">
                        <label for="memory" class="form-check-label bold me-2 text-nowrap"><?= _('Memory') ?> <?= xi6_info_tooltip(_("Monitors the memory usage")) ?></label>
                    </div>
                    <div class="col-sm-6 offset-sm-2">
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i <?= xi6_title_tooltip(_('Warning Threshold (default = 70)')) ?> class="material-symbols-outlined md-warning md-18 md-400">warning</i>
                                    </span>
                                    <input type="text" name="serviceargs[memory][warning]" id="memory_warning" value="<?= encode_form_val($serviceargs["memory"]["warning"] ?? 70) ?>" class="form-control form-control-sm">
                                    <i id="services_memory_warning_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i <?= xi6_title_tooltip(_('Critical Threshold (default = 90)')) ?> class="material-symbols-outlined md-critical md-18 md-400">error</i>
                                    </span>
                                    <input type="text" name="serviceargs[memory][critical]" id="memory_critical" value="<?= encode_form_val($serviceargs["memory"]["critical"] ?? 90) ?>" class="form-control form-control-sm">
                                    <i id="services_memory_critical_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <fieldset class="row g-2 mb-1 wz-fieldset align-items-center metrics">
                    <div class="form-check col-sm-2 d-flex align-items-center">
                        <input type="checkbox" id="disk" class="form-check-input me-2" name="services[disk]" <?= isset($services["disk"]) && $services["disk"] ? 'checked="checked"' : '' ?> onchange="updateSelectAll('metrics')">
                        <label for="disk" class="form-check-label bold me-2 text-nowrap"><?= _('Disk') ?> <?= xi6_info_tooltip(_("Monitors the disk space usage")) ?></label>
                    </div>
                    <div class="col-sm-6 offset-sm-2">
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i <?= xi6_title_tooltip(_('Warning Threshold (default = 70)')) ?> class="material-symbols-outlined md-warning md-18 md-400">warning</i>
                                    </span>
                                    <input type="text" name="serviceargs[disk][warning]" id="disk_warning" value="<?= encode_form_val($serviceargs["disk"]["warning"] ?? 70) ?>" class="form-control form-control-sm">
                                    <i id="services_disk_warning_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i <?= xi6_title_tooltip(_('Critical Threshold (default = 90)')) ?> class="material-symbols-outlined md-critical md-18 md-400">error</i>
                                    </span>
                                    <input type="text" name="serviceargs[disk][critical]" id="disk_critical" value="<?= encode_form_val($serviceargs["disk"]["critical"] ?? 90) ?>" class="form-control form-control-sm">
                                    <i id="services_disk_critical_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
            <fieldset class="row g-2 mb-1 wz-fieldset align-items-center metrics">
                <div class="form-check col-sm-2 d-flex align-items-center">
                <input type="checkbox" id="uptime" class="form-check-input me-2" name="services[uptime]" <?= isset($services["uptime"]) && $services["uptime"] ? 'checked="checked"' : '' ?> onchange="updateSelectAll('metrics')">
                <label for="uptime" class="form-check-label bold me-2 text-nowrap"><?= _('Uptime') ?> <?= xi6_info_tooltip(_("Monitors the system uptime")) ?></label>
                </div>
                <div class="col-sm-6 offset-sm-2">
                <div class="row">
                    <div class="col-sm-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                        <i <?= xi6_title_tooltip(_('Warning Threshold (default = 3600)')) ?> class="material-symbols-outlined md-warning md-18 md-400">warning</i>
                        </span>
                        <input type="text" name="serviceargs[uptime][warning]" id="uptime_warning" value="<?= encode_form_val($serviceargs["uptime"]["warning"] ?? 3600) ?>" class="form-control form-control-sm">
                        <i id="services_uptime_warning_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                    </div>
                    </div>
                    <div class="col-sm-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                        <i <?= xi6_title_tooltip(_('Critical Threshold (default = 1800)')) ?> class="material-symbols-outlined md-critical md-18 md-400">error</i>
                        </span>
                        <input type="text" name="serviceargs[uptime][critical]" id="uptime_critical" value="<?= encode_form_val($serviceargs["uptime"]["critical"] ?? 1800) ?>" class="form-control form-control-sm">
                        <i id="services_uptime_critical_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                    </div>
                    </div>
                </div>
                </div>
            </fieldset>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
            <fieldset class="row g-2 mb-1 wz-fieldset align-items-center metrics">
                <div class="form-check col-sm-2 d-flex align-items-center">
                <input type="checkbox" id="net_in" class="form-check-input me-2" name="services[net_in]" <?= isset($services["net_in"]) && $services["net_in"] ? 'checked="checked"' : '' ?> onchange="updateSelectAll('metrics')">
                <label for="net_in" class="form-check-label bold me-2 text-nowrap"><?= _('Network In') ?> <?= xi6_info_tooltip(_("Monitors incoming network traffic")) ?></label>
                </div>
                <div class="col-sm-6 offset-sm-2">
                <div class="row">
                    <div class="col-sm-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                        <i <?= xi6_title_tooltip(_('Warning Threshold (default = 80)')) ?> class="material-symbols-outlined md-warning md-18 md-400">warning</i>
                        </span>
                        <input type="text" name="serviceargs[net_in][warning]" id="net_in_warning" value="<?= encode_form_val($serviceargs["net_in"]["warning"] ?? 80) ?>" class="form-control form-control-sm">
                        <i id="services_net_in_warning_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                    </div>
                    </div>
                    <div class="col-sm-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                        <i <?= xi6_title_tooltip(_('Critical Threshold (default = 95)')) ?> class="material-symbols-outlined md-critical md-18 md-400">error</i>
                        </span>
                        <input type="text" name="serviceargs[net_in][critical]" id="net_in_critical" value="<?= encode_form_val($serviceargs["net_in"]["critical"] ?? 95) ?>" class="form-control form-control-sm">
                        <i id="services_net_in_critical_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                    </div>
                    </div>
                    <div class="col-sm-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                        <i <?= xi6_title_tooltip(_('Network Interface (e.g. eth0, ens33)')) ?> class="material-symbols-outlined md-18 md-400">settings_ethernet</i>
                        </span>
                        <input type="text" name="serviceargs[net_in][interface]" id="net_in_interface" value="<?= encode_form_val($serviceargs["net_in"]["interface"] ?? '') ?>" class="form-control form-control-sm" placeholder="<?= _('Interface') ?>">
                        <i id="services_net_in_interface_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                    </div>
                    </div>
                </div>
                </div>
            </fieldset>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
            <fieldset class="row g-2 mb-1 wz-fieldset align-items-center metrics">
                <div class="form-check col-sm-2 d-flex align-items-center">
                <input type="checkbox" id="net_out" class="form-check-input me-2" name="services[net_out]" <?= isset($services["net_out"]) && $services["net_out"] ? 'checked="checked"' : '' ?> onchange="updateSelectAll('metrics')">
                <label for="net_out" class="form-check-label bold me-2 text-nowrap"><?= _('Network Out') ?> <?= xi6_info_tooltip(_("Monitors outgoing network traffic")) ?></label>
                </div>
                <div class="col-sm-6 offset-sm-2">
                <div class="row">
                    <div class="col-sm-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                        <i <?= xi6_title_tooltip(_('Warning Threshold (default = 80)')) ?> class="material-symbols-outlined md-warning md-18 md-400">warning</i>
                        </span>
                        <input type="text" name="serviceargs[net_out][warning]" id="net_out_warning" value="<?= encode_form_val($serviceargs["net_out"]["warning"] ?? 80) ?>" class="form-control form-control-sm">
                        <i id="services_net_out_warning_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                    </div>
                    </div>
                    <div class="col-sm-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                        <i <?= xi6_title_tooltip(_('Critical Threshold (default = 95)')) ?> class="material-symbols-outlined md-critical md-18 md-400">error</i>
                        </span>
                        <input type="text" name="serviceargs[net_out][critical]" id="net_out_critical" value="<?= encode_form_val($serviceargs["net_out"]["critical"] ?? 95) ?>" class="form-control form-control-sm">
                        <i id="services_net_out_critical_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                    </div>
                    </div>
                    <div class="col-sm-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                        <i <?= xi6_title_tooltip(_('Network Interface (e.g. eth0, ens33)')) ?> class="material-symbols-outlined md-18 md-400">settings_ethernet</i>
                        </span>
                        <input type="text" name="serviceargs[net_out][interface]" id="net_out_interface" value="<?= encode_form_val($serviceargs["net_out"]["interface"] ?? '') ?>" class="form-control form-control-sm" placeholder="<?= _('Interface') ?>">
                        <i id="services_net_out_interface_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                    </div>
                    </div>
                </div>
                </div>
            </fieldset>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <fieldset class="row g-2 mb-1 wz-fieldset align-items-center metrics">
                    <div class="form-check col-sm-2 d-flex align-items-center">
                        <input type="checkbox" id="process_count" class="form-check-input me-2" name="services[process_count]" <?= isset($services["process_count"]) && $services["process_count"] ? 'checked="checked"' : '' ?> onchange="updateSelectAll('metrics')">
                        <label for="process_count" class="form-check-label bold me-2 text-nowrap"><?= _('Process Count') ?> <?= xi6_info_tooltip(_("Monitors the number of running processes")) ?></label>
                    </div>
                    <div class="col-sm-6 offset-sm-2">
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i <?= xi6_title_tooltip(_('Warning Threshold (default = 300)')) ?> class="material-symbols-outlined md-warning md-18 md-400">warning</i>
                                    </span>
                                    <input type="text" name="serviceargs[process_count][warning]" id="process_count_warning" value="<?= encode_form_val($serviceargs["process_count"]["warning"] ?? 300) ?>" class="form-control form-control-sm">
                                    <i id="services_process_count_warning_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i <?= xi6_title_tooltip(_('Critical Threshold (default = 500)')) ?> class="material-symbols-outlined md-critical md-18 md-400">error</i>
                                    </span>
                                    <input type="text" name="serviceargs[process_count][critical]" id="process_count_critical" value="<?= encode_form_val($serviceargs["process_count"]["critical"] ?? 500) ?>" class="form-control form-control-sm">
                                    <i id="services_process_count_critical_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <fieldset class="row g-2 mb-1 wz-fieldset align-items-center metrics">
                    <div class="form-check col-sm-2 d-flex align-items-center">
                        <input type="checkbox" id="cpu_load" class="form-check-input me-2" name="services[cpu_load]" <?= isset($services["cpu_load"]) && $services["cpu_load"] ? 'checked="checked"' : '' ?> onchange="updateSelectAll('metrics')">
                        <label for="cpu_load" class="form-check-label bold me-2 text-nowrap"><?= _('CPU Load') ?> <?= xi6_info_tooltip(_("Monitors the system CPU load average")) ?></label>
                    </div>
                    <div class="col-sm-6 offset-sm-2">
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i <?= xi6_title_tooltip(_('Warning Threshold (default = 5)')) ?> class="material-symbols-outlined md-warning md-18 md-400">warning</i>
                                    </span>
                                    <input type="text" name="serviceargs[cpu_load][warning]" id="cpu_load_warning" value="<?= encode_form_val($serviceargs["cpu_load"]["warning"] ?? 5) ?>" class="form-control form-control-sm">
                                    <i id="services_cpu_load_warning_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i <?= xi6_title_tooltip(_('Critical Threshold (default = 10)')) ?> class="material-symbols-outlined md-critical md-18 md-400">error</i>
                                    </span>
                                    <input type="text" name="serviceargs[cpu_load][critical]" id="cpu_load_critical" value="<?= encode_form_val($serviceargs["cpu_load"]["critical"] ?? 10) ?>" class="form-control form-control-sm">
                                    <i id="services_cpu_load_critical_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </div> <!-- container -->

    <script type="text/javascript" src="<?= get_base_url() ?>includes/js/wizards-bs5.js?<?= get_build_id(); ?>"></script>
    <script type="text/javascript">
        function selectAllInSection(sectionClass, select) {
            const checkboxes = document.querySelectorAll(`.${sectionClass} input[type="checkbox"]`);
            checkboxes.forEach(checkbox => {
                checkbox.checked = select;
            });
        }

        function updateSelectAll(sectionClass) {
            const checkboxes = document.querySelectorAll(`.${sectionClass} input[type="checkbox"]`);
            const selectAllCheckbox = document.getElementById(`select_all_${sectionClass}`);
            const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
            selectAllCheckbox.checked = allChecked;
        }
    </script>
