<?php
/**
 * EVTP Training Plans
 *
 * Registrar training plan creation.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Get passed parameters.

// Set up basic information.
$url     = new moodle_url('/local/evtp/plancreate.php');
$context = context_system::instance();
$title   = get_string('createnewtrainingplan', 'local_evtp');

// Sanity checks.
require_login();
require_capability('local/evtp:manage', $context);

// Set up page.
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->set_cacheable(false);
$PAGE->navbar->add(get_string('trainingplans', 'local_evtp'));
$PAGE->navbar->add(get_string('trainingplansearch', 'local_evtp'), new moodle_url('/local/evtp/plansearch.php'));
$PAGE->navbar->add($title);

$output = $PAGE->get_renderer('local_evtp');

// We do this call before outputting header in case there is a redirect from the form.
$out = $output->training_plan_create();

echo $output->header();
echo $out;
echo $output->footer();
