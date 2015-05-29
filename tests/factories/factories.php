<?php
/**
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 */

$factory(
    \SebastianBerc\Repositories\Test\ModelStub::class,
    ['email' => $faker->companyEmail, 'password' => 'secret']
);

$factory(
    \SebastianBerc\Repositories\Test\CacheModelStub::class,
    ['email' => $faker->companyEmail, 'password' => 'secret']
);

$factory(
    \SebastianBerc\Repositories\Test\User::class,
    ['email' => $faker->companyEmail, 'password' => 'secret']
);

$factory(
    \SebastianBerc\Repositories\Test\PasswordReset::class,
    [
        'user_id' => 'factory:' . \SebastianBerc\Repositories\Test\User::class,
        'token'   => $faker->md5
    ]
);
