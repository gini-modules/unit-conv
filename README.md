# Unit Conversion

## How to Use It
```php
$conv = \Gini\Unit\Conversion::of('cas/64-17-5');
$gram = $conv->from('100ml')->to('g');

list($value, $unit) = $conv->parse('100ml');

$units = $conv->getUnits();
```

## Command Line
### Make a Conversion
```bash
gini unit-conv convert object=liquid from=100mg to=l
```

### List Units
```bash
gini unit-conv list-units
```