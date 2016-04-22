# Unit Conversion

## How to Use It
```php
$conversion = \Gini\Unit\Conversion::of('cas/64-17-5');
$gram = $conversion->from('100ml')->to('g');

list($value, $unit) = $conversion->parse('100ml');

$units = $conversion->getUnits();
```

## Add Conversion
```bash
gini unit-conversion set cas/64-17-5 1g=1ml
```
