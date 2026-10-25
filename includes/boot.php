<?php
/**
 * Boot file — included at the top of every page.
 * Loads config, database, helpers, and starts the session.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

sessionStart();
