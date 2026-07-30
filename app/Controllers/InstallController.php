<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Installer;
use App\Core\Url;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * First-run database setup wizard (UI; no manual PHP edits).
 */
class InstallController extends Controller
{
    /**
     * Show the setup form or process a submitted configuration.
     */
    public function index(): void
    {
        $appUrl = Url::app();
        $baseUrl = Url::assets();
        $homeUrl = Url::project() === '' ? '/' : rtrim(Url::project(), '/') . '/';
        $alreadyInstalled = Installer::isInstalled();
        $config = Installer::defaultConfig();
        $errors = [];
        $success = null;
        $importSql = true;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if ($alreadyInstalled && empty($_POST['force_reinstall'])) {
                $errors[] = 'Already installed. Delete config/installed.lock to run setup again, or check Force reinstall.';
            } elseif (!Csrf::validate(Csrf::tokenFromRequest())) {
                $errors[] = 'Invalid security token. Refresh the page and try again.';
            } else {
                [$config, $fieldErrors] = Installer::normalizeInput($_POST);
                $errors = $fieldErrors;
                $importSql = !empty($_POST['import_sql']);

                if ($errors === []) {
                    try {
                        if ($alreadyInstalled && !empty($_POST['force_reinstall'])) {
                            @unlink(Installer::lockPath());
                        }
                        Installer::install($config, $importSql);
                        $alreadyInstalled = true;
                        $success = $importSql
                            ? 'Setup complete. Database created/imported and credentials saved.'
                            : 'Setup complete. Credentials saved (SQL import skipped).';
                    } catch (PDOException $e) {
                        $errors[] = 'Database error: ' . $e->getMessage();
                    } catch (RuntimeException $e) {
                        $errors[] = $e->getMessage();
                    } catch (Throwable $e) {
                        $errors[] = 'Unexpected error: ' . $e->getMessage();
                    }
                }
            }
        }

        $this->view('install/setup', [
            'title' => 'Setup',
            'baseUrl' => $baseUrl,
            'appUrl' => $appUrl,
            'homeUrl' => $homeUrl,
            'csrfToken' => Csrf::token(),
            'config' => $config,
            'errors' => $errors,
            'success' => $success,
            'alreadyInstalled' => $alreadyInstalled,
            'importSql' => $importSql,
        ], 'install');
    }
}
