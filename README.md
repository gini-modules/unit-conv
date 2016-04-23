# Unit Conversion

## How to Use It
```php
$conv = \Gini\Unit\Conversion::of('cas/64-17-5');
$gram = $conv->from('100ml')->to('g');

list($value, $unit) = $conv->parse('100ml');

$units = $conv->getUnits();
```

## Add Conversion
```bash
gini unit-conv set object=liquid from=weight to=volume factor=10
gini unit-conv convert object=liquid from=100mg to=l
```
