<?php
/**
 * Important file load. init.php is loader file.
 */
require '../../init.php';

require_once 'sample_model.php';
$sample_model=new sample_model();
echo '<pre>';
var_dump($sample_model->get_rows());