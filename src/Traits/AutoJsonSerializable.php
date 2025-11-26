<?php

namespace PHPRag\Traits;

use ReflectionClass;
use ReflectionProperty;

trait AutoJsonSerializable
{
  protected array $hidden = ['hidden'];

  public function toArray(): array
  {
    $ref = new ReflectionClass($this);
    $props = $ref->getProperties(
      ReflectionProperty::IS_PUBLIC |
        ReflectionProperty::IS_PROTECTED |
        ReflectionProperty::IS_PRIVATE
    );

    $data = [];

    foreach ($props as $prop) {
      $name = $prop->getName();
      if (in_array($name, $this->hidden, true)) {
        continue;
      }
      if (!$prop->isPublic()) {
        $prop->setAccessible(true);
      }
      $value = $prop->getValue($this);
      $snake = strtolower(preg_replace('/[A-Z]/', '_$0', $name));
      $data[$snake] = $value;
    }

    return $data;
  }

  public function toJson(int $flags = JSON_UNESCAPED_UNICODE): string
  {
    return json_encode($this->toArray(), $flags);
  }
}
