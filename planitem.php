<?php
/**
 * EVTP Training Plans
 *
 * Registrar training plan item.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Get passed parameters.
$id = required_param('id', PARAM_INT);
$regplanid = required_param('regplanid', PARAM_INT);

// Pre-setup checks.
$regplan = new \local_evtp\regplan($regplanid);
if (!($DB->record_exists('local_evtp_regitem', array('id'=>$id)))) {
    redirect(new moodle_url('/local/evtp/plansearch.php'), get_string('invaliditemid', 'local_evtp'));
    exit;
}

// Set up basic information.
$url     = new moodle_url('/local/evtp/planitem.php', array('id' => $id, 'regplanid' => $regplanid));
$context = context_system::instance();
$title   = get_string('trainingplanitem', 'local_evtp');

// Sanity checks.
require_login();
require_capability('local/evtp:view', $context);

// Set up page.
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->set_cacheable(false);
$PAGE->navbar->add(get_string('trainingplans', 'local_evtp'));
$PAGE->navbar->add(get_string('trainingplansearch', 'local_evtp'), new moodle_url('/local/evtp/plansearch.php'));
$PAGE->navbar->add(get_string('trainingplanview', 'local_evtp'), new moodle_url('/local/evtp/plansearch.php', array('id'=>$regplanid)));
$PAGE->navbar->add($title);


$output = $PAGE->get_renderer('local_evtp');

$out = $output->training_plan_item($regplan, $id);

echo $output->header();
echo $out;
echo $output->footer();
