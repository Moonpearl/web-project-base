<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use App\Entity\<?= $entity_class ?>;
use App\Controller\Base\RestController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("<?= $route_path ?>", name="<?= $route_name ?>")
 */
class <?= $class_name; ?> extends RestController<?= "\n" ?>
{
    const ENTITY = <?= $entity_class ?>::class;
}
