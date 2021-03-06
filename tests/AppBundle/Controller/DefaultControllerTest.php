<?php

namespace Tests\AppBundle\Controller;

use AppBundle\DataFixtures\ORM\LoadPostData;
use AppBundle\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    private $client;

    public function testIndex()
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals(1, $crawler->filter('h1')->count());
    }

    public function test404()
    {
        $this->client->request('GET', '/the-page-that=doesn\'t exist');

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testDynamicPosts()
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertGreaterThan(0, $crawler->filter('h2')->count());

        $postHeaders = $crawler->filter('h2 > a');
        $this->assertEquals('Sample blog post', $postHeaders->text());

        $postHeadersValues = $postHeaders->extract(array('_text'));
        foreach ($postHeadersValues as $postHeaderValue) {
            $link = $crawler->selectLink($postHeaderValue)->link();
            $this->client->click($link);
            $this->assertTrue($this->client->getResponse()->isSuccessful());
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->client = static::createClient();

        $em = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        $loader = new ContainerAwareLoader($this->client->getContainer());
        $loader->addFixture(new LoadUserData());
        $loader->addFixture(new LoadPostData());

        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());
    }

}
