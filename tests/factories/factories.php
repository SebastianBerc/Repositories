<?php
/**
 * @author Sebastian Berć <sebastian.berc@gmail.com>
 */

$factory(
    \SebastianBerc\Repositories\Test\ModelStub::class,
    ['email' => $faker->companyEmail, 'password' => 'secret']
);
