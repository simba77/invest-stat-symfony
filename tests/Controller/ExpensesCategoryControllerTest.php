<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExpensesCategoryControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = parent::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('admin@admin.com');
        $client->loginUser($testUser);

        $client->request('GET', '/api/expenses');
        $responseContent = $client->getResponse()->getContent();

        $this->assertResponseIsSuccessful();

        $this->assertJsonStringEqualsJsonFile(
            __DIR__ . '/../responses/expensesCategories.json',
            $responseContent
        );
    }
}
