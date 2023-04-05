<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Object\Traits;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait PopulateObjectTrait
{
    /**
     * Return the Object instance to use in this tests.
     *
     * @return object
     */
    abstract protected function buildObject();

    /**
     * @param array $data
     *
     * @return object
     */
    protected function generateObjectPopulated($data = array())
    {
        //Build a new instance of this object
        $ObjectObject = $this->buildObject();

        if (!empty($data)) {
            //We must populate the object's var, we use ReflectionClass api to bypass visibility scope constraints
            $reflectionClassObject = new \ReflectionClass($ObjectObject);

            foreach ($data as $fieldName => $value) {
                if ($reflectionClassObject->hasProperty($fieldName)) {
                    $propertyObject = $reflectionClassObject->getProperty($fieldName);
                    $propertyObject->setAccessible(true);
                    $propertyObject->setValue($ObjectObject, $value);
                } elseif ($reflectionClassObject->getParentClass()->hasProperty($fieldName)) {
                    $propertyObject = $reflectionClassObject->getParentClass()->getProperty($fieldName);
                    $propertyObject->setAccessible(true);
                    $propertyObject->setValue($ObjectObject, $value);
                }
            }
        }

        return $ObjectObject;
    }
}
