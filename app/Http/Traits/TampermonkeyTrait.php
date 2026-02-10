<?php

namespace App\Http\Traits;

use App\Models\Domain;

trait TampermonkeyTrait
{
    public function createScript()
    {
        $domains = Domain::with('database')->get();
        $hosts = [];

        $credsString = "const credentialsMap = {\n";
        foreach ($domains as $domain) {
            $credsString .= "'{$domain->name}': {\n" .
                "username: '{$domain->database->username}',\n" .
                "password: '{$domain->database->password}'\n" .
                "},\n";

            if (!in_array($domain->database->host, $hosts))
                $hosts[] = $domain->database->host;
        }
        $credsString .= "};\n\n";

        $hostsString = "const allowedHosts = [\n";
        foreach ($hosts as $host) {
            $hostsString .= "'{$host}',\n";
        }
        $hostsString .= "];\n\n";

        return "// ==UserScript==\n" .
            "// @name         PMA Auto-Login with host filter\n" .
            "// @namespace    http://tampermonkey.net/\n" .
            "// @version      1.1\n" .
            "// @description  Автоматический вход в phpMyAdmin по URL-параметру auth_tag и только на указанных хостах\n" .
            "// @author       Ruslan\n" .
            "// @match        *://*/*\n" .
            "// @grant        none\n" .
            "// ==/UserScript==\n\n" .
            "(function () {\n" .
            "'use strict';\n\n" .
            $hostsString .
            $credsString .
            "const currentHost = window.location.hostname;\n" .
            "if (!allowedHosts.includes(currentHost)) {\n" .
            "console.log('❌ Хост не в списке разрешённых');\n" .
            "return;\n" .
            "}\n\n" .
            "const params = new URLSearchParams(window.location.search);\n" .
            "const tag = params.get('auth_tag');\n" .
            "const creds = credentialsMap[tag];\n\n" .
            "if (!creds) {\n" .
            "console.log('❌ Нет нужного auth_tag');\n" .
            "return;\n" .
            "}\n\n" .
            "console.log('✅ Условие пройдено: начинаем авторизацию');\n\n" .
            "function doAutoLogin() {\n" .
            "console.log('📦 Страница загружена, заполняем форму');\n\n" .
            "try {\n" .
            "const userInput = document.querySelector('input[name=\"pma_username\"]');\n" .
            "const passInput = document.querySelector('input[name=\"pma_password\"]');\n" .
            "const hostInput = document.querySelector('input[name=\"server\"]') || document.querySelector('select[name=\"server\"]');\n" .
            "const submitButton = document.querySelector('#input_go');\n\n" .
            "if (userInput) userInput.value = creds.username;\n" .
            "if (passInput) passInput.value = creds.password;\n\n" .
            "if (hostInput && creds.hosts.length > 0) {\n" .
            "if (hostInput.tagName.toLowerCase() === 'input') {\n" .
            "hostInput.value = creds.hosts[0];\n" .
            "} else if (hostInput.tagName.toLowerCase() === 'select') {\n" .
            "for (let opt of hostInput.options) {\n" .
            "if (creds.hosts.includes(opt.value)) {\n" .
            "opt.selected = true;\n" .
            "break;\n" .
            "}\n" .
            "}\n" .
            "}\n" .
            "}\n\n" .
            "if (submitButton) {\n" .
            "console.log('🚀 Отправляем форму');\n" .
            "submitButton.click();\n" .
            "}\n" .
            "} catch (err) {\n" .
            "console.error('[PMA AutoLogin Error]', err);\n" .
            "}\n" .
            "}\n" .
            "if (document.readyState === 'complete') {\n" .
            "doAutoLogin();\n" .
            "} else {\n" .
            "window.addEventListener('load', doAutoLogin);\n" .
            "}\n" .
            "})();\n";
    }
}
