<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Nongbit\Twig\Traits\TwigTrait;
use Psr\Log\LoggerInterface;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

abstract class BaseController extends Controller
{
    use TwigTrait;

    protected $request;

    protected $helpers = ['auth', 'form', 'geo'];

    private static bool $twigFunctionsRegistered = false;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->initTwig();

        $usersModel = new \App\Models\UsersModel();
        $lokasiModel = new \App\Models\LokasiPresensiModel();

        if (function_exists('user_id') && user_id()) {
            $user_profile = $usersModel->getUserInfo(user_id());
            if ($user_profile) {
                $user_lokasi = $lokasiModel->getWhere(['nama_lokasi' => $user_profile->lokasi_presensi])->getFirstRow();

                if ($user_lokasi && in_array($user_lokasi->zona_waktu, timezone_identifiers_list())) {
                    date_default_timezone_set($user_lokasi->zona_waktu);
                } else {
                    date_default_timezone_set('Asia/Jakarta');
                }
            }
        }
    }

    protected function view(string $template, array $data = []): string
    {
        $data['authUser'] = $this->getAuthUser();
        $data['currentUrl'] = uri_string();
        $data['appName'] = 'O-Present';
        $data['session'] = service('session');

        $this->twig->addGlobals([
            'session' => service('session'),
        ]);

        // Register Twig functions only once — the singleton persists across
        // in-process test requests and Twig throws on duplicate registrations.
        if (! self::$twigFunctionsRegistered) {
            $this->twig->addFunctions([
                'vite' => function (string $entry) {
                    $manifestPath = ROOTPATH . 'public/build/.vite/manifest.json';
                    if (! is_file($manifestPath)) {
                        return '/assets/' . $entry;
                    }
                    $manifest = json_decode(file_get_contents($manifestPath), true);

                    $key = 'resources/' . $entry;
                    if (isset($manifest[$key]['file'])) {
                        return '/' . $manifest[$key]['file'];
                    }

                    if (isset($manifest[$entry]['file'])) {
                        return '/' . $manifest[$entry]['file'];
                    }

                    foreach ($manifest as $k => $v) {
                        if (isset($v['src']) && basename($v['src']) === $entry) {
                            return '/' . $v['file'];
                        }
                    }

                    return '/assets/' . $entry;
                },
                'vite_css' => function (string $entry) {
                    $manifestPath = ROOTPATH . 'public/build/.vite/manifest.json';
                    if (! is_file($manifestPath)) {
                        return '';
                    }
                    $manifest = json_decode(file_get_contents($manifestPath), true);

                    $key = 'resources/' . $entry;
                    $cssFiles = $manifest[$key]['css'] ?? $manifest[$entry]['css'] ?? [];

                    if (! $cssFiles) {
                        foreach ($manifest as $k => $v) {
                            if (isset($v['src']) && basename($v['src']) === $entry) {
                                $cssFiles = $v['css'] ?? [];
                                break;
                            }
                        }
                    }

                    $tags = [];
                    foreach ($cssFiles as $css) {
                        $tags[] = '<link rel="stylesheet" href="/' . $css . '">';
                    }

                    return implode("\n  ", $tags);
                },
                'old' => function (string $key, string $default = '') {
                    return old($key, $default);
                },
                'csrf_field' => function () {
                    return csrf_field();
                },
                'csrf_token' => function () {
                    return csrf_token();
                },
                'csrf_hash' => function () {
                    return csrf_hash();
                },
            ]);
            self::$twigFunctionsRegistered = true;
        }

        return $this->twig->render($template, $data);
    }

    protected function getAuthUser(): ?object
    {
        $auth = service('authentication');

        // Use isLoggedIn() instead of user_id() — user_id() calls check() which
        // throws RedirectException for force_pass_reset users (broken in CI4 4.7+).
        if (! $auth->isLoggedIn() || ! $auth->id()) {
            return null;
        }

        $usersModel = new \App\Models\UsersModel();
        $user = $usersModel->getUserInfo($auth->id());
        
        if ($user) {
            if (!empty($user->foto) && $user->foto !== 'default.jpg') {
                $photoService = new \App\Services\PhotoService();
                $user->foto_url = $photoService->getProfilePhotoUrl($user->foto);
            } else {
                $user->foto_url = base_url('images/default-avatar.svg');
            }
        }

        return $user;
    }

}
