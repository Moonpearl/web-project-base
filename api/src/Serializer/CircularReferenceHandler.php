<?php

namespace App\Serializer;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CircularReferenceHandler {
    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public function __invoke($topic)
    {
        $topicClass = get_class($topic);

        if (!defined($topicClass . '::ROUTE')) {
            throw new ClassConfigurationException($topicClass . '::ROUTE constant not set.');
        }

        return $this->router->generate($topicClass::ROUTE . '_find_by_id', [
            'id' => $topic->getId(),
        ]);
    }
}
