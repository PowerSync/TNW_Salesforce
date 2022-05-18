<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service;

use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use RuntimeException;
use stdClass;

/**
 * Object convertor service.
 */
class ObjectConvertor
{
    /**
     * Convert object ro array.
     *
     * @param $object
     *
     * @return array
     * @throws ReflectionException
     */
    public function toArray($object): array
    {
        if (!is_object($object)) {
            throw new RuntimeException(__('Specified param is not object')->render());
        }

        $data['class_name'] = get_class($object);
        $reflectionClass = new ReflectionClass($object);
        foreach ($reflectionClass->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($object);
            if (!(is_array($value) || is_object($value))) {
                $data[$property->name] = $value;
                continue;
            }

            $convertedValue = $this->covertToArray($value);
            $data[$property->name] = $convertedValue;
        }

        // Convert not declared properties to array.
        foreach (get_object_vars($object) as $name => $value) {
            if (isset($data[$name])) {
                continue;
            }

            $value = is_array($value) || is_object($value) ? $this->covertToArray($value) : $value;
            $data[$name] = $value;
        }

        return $data;
    }

    /**
     * Convert array to object.
     *
     * @param array $data
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function toObject(array $data)
    {
        $className = $data['class_name'] ?? null;
        if (!($className && class_exists($className))) {
            return null;
        }

        unset($data['class_name']);
        $object = new $className();
        $reflectionClass = new ReflectionClass($object);
        foreach ($data as $name => $value) {
            if ($reflectionClass->hasProperty($name)) {
                $property = $reflectionClass->getProperty($name);
                $property->setAccessible(true);
                if (!(is_array($value) || is_object($value))) {
                    $property->setValue($object, $value);
                    continue;
                }

                $convertedValue = $this->convertToObject($value);
                $property->setValue($object, $convertedValue);
            } else {
                $object->$name = $value;
            }

        }

        return $object;
    }

    /**
     * @param $value
     *
     * @return array|mixed
     * @throws ReflectionException
     */
    private function covertToArray($value)
    {
        if (is_array($value)) {
            $castedArray = [];
            foreach ($value as $item) {
                $castedArray[] = $this->covertToArray($item);
            }

            return $castedArray;
        }

        if (!is_object($value)) {
            return $value;
        }

        $data = [];
        foreach ((array)$value as $propertyName => $prpertyValue) {
            $propertyName = str_replace('*', '', $propertyName);
            $data[$propertyName] = $this->covertToArray($prpertyValue);
        }

        $data['class_name'] = get_class($value);

        return $data;
    }

    /**
     * @param mixed $data
     *
     * @return array|mixed|object
     * @throws ReflectionException
     */
    private function convertToObject($data)
    {
        $originClassName = $data['class_name'] ?? null;
        if (is_array($data) && !$originClassName) {
            $convertedObjects = [];
            foreach ($data as $item) {
                $convertedObjects[] = $this->convertToObject($item);
            }

            return $convertedObjects;
        }

        if (!(is_string($originClassName) && class_exists($originClassName))) {
            return $data;
        }

        unset($data['class_name']);
        if ($originClassName === stdClass::class) {
            return (object)$data;
        }

        $destination = new $originClassName();
        $destinationReflection = new ReflectionObject($destination);
        foreach ($data as $name => $value) {
            if (is_array($value)) {
                $value = $value ? $this->convertToObject($value) : [];
            }

            $name = trim($name);
            if ($destinationReflection->hasProperty($name)) {
                $propertyDest = $destinationReflection->getProperty($name);
                $propertyDest->setAccessible(true);
                $propertyDest->setValue($destination, $value);
            } else {
                $destination->$name = $value;
            }
        }

        return $destination;
    }
}
