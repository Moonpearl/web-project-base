<?php

namespace App\Serializer;

use Psr\Log\LoggerInterface;
use App\Exception\ClassConfigurationException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

class EntityNormalizer implements ContextAwareNormalizerInterface
{
    private $router;
    private $normalizer;

    public function __construct(UrlGeneratorInterface $router, ObjectNormalizer $normalizer)
    {
        $this->router = $router;
        $this->normalizer = $normalizer;
    }

    public function normalize($topic, $format = null, array $context = [])
    {
        $topicClass = get_class($topic);

        if (!defined($topicClass . '::ROUTE')) {
            throw new ClassConfigurationException($topicClass . '::ROUTE constant not set.');
        }

        $needSerialization = false;

        if (isset($context['entities'])) {
            foreach($context['entities'] as $entityClass) {
                $regexp = '/' . str_replace('\\', '\\\\', $entityClass) . '$/';

                if (preg_match($regexp, $topicClass)) {
                    $needSerialization = true;
                    break;
                }
            }
        }

        if ($needSerialization) {
            $object = $this->normalizer->normalize($topic, $format, $context);
            $object['__type'] = $topicClass::ROUTE;
            return $object;
        } else {
            return $this->router->generate($topicClass::ROUTE . '_find_by_id', [
                'id' => $topic->getId(),
            ]);
        }
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        // Supports only objects
        if (!is_object($data)) {
            return false;
        }

        // Supports only objects inside the App\Entity namespace
        if (preg_match('/App\\\Entity\\\([\w\\_]+)$/', get_class($data))) {
            return true;
        }

        return false;
    }
}
