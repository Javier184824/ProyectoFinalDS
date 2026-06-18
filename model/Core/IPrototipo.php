<?php

namespace model\Core;

/**
 * Interfaz del patrón Prototype. Todo objeto clonable debe implementarla.
 */
interface IPrototipo
{
    public function clonar(): static;
}

// Dos puntos clave en esta traducción:
// 
// - **`interface`**: Se mantiene igual en PHP, con la diferencia de que los métodos no llevan modificador de acceso en C# pero en PHP se declaran con `public`.
// - **Tipo de retorno `IPrototipo`**: Se usa `static` en lugar de `IPrototipo` como tipo de retorno, que es más preciso en PHP ya que garantiza que cada clase que implemente la interfaz retorne su propio tipo concreto, respetando mejor el principio del patrón Prototype.