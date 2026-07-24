<?php

/**
 * Registro público desabilitado em routes/auth.php (criação via create:user / Artisan).
 */
test('registration screen can be rendered', function () {
    $this->markTestSkipped('Rota /register desabilitada — usuários via Artisan create:user.');
});

test('new users can register', function () {
    $this->markTestSkipped('Rota /register desabilitada — usuários via Artisan create:user.');
});
