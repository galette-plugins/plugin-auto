<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\Controllers;

use DI\Attribute\Inject;
use Galette\Controllers\AbstractPluginController;
use GaletteAuto\Auto;
use GaletteAuto\AutoPreferences;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

/**
 * Galette Auto plugin preferences controller
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class PreferencesController extends AbstractPluginController
{
    /**
     * @var array<string, mixed>
     */
    #[Inject("Plugin Galette Auto")]
    protected array $module_info;

    /**
     * Preferences page
     */
    public function preferences(Request $request, Response $response): Response
    {
        $auto = new Auto($this->plugins, $this->zdb);
        $fields = [];
        foreach (array_keys(AutoPreferences::OPTIONAL_FIELDS) as $field) {
            $fields[$field] = $auto->getPropName($field);
        }

        $this->view->render(
            $response,
            $this->getTemplate('preferences'),
            [
                'page_title'    => _T("Cars preferences", "auto"),
                'fields'        => $fields,
                'required'      => (new AutoPreferences($this->preferences))->toArray(),
            ]
        );
        return $response;
    }

    /**
     * Store preferences
     *
     * Every preference is a yes/no one: a missing one is an unchecked box,
     * and is set off.
     */
    public function storePreferences(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();
        $errors = [];

        foreach (array_keys(AutoPreferences::getSchema()) as $name) {
            if (!$this->preferences->setValue($name, (int)isset($post[$name]), $this->login)) {
                $errors = array_merge($errors, $this->preferences->getErrors());
            }
        }

        if (count($errors) === 0) {
            $this->flash->addMessage(
                'success_detected',
                _T("Preferences have been successfully stored!", "auto")
            );
        } else {
            foreach (array_unique($errors) as $error) {
                $this->flash->addMessage('error_detected', $error);
            }
        }

        return $response
            ->withStatus(302)
            ->withHeader('Location', $this->routeparser->urlFor('autoPreferences'));
    }
}
