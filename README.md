# Unit Conversion 单位转换

`unit-conv`库会与`unit-conv-server`搭配使用, 向`unit-conv-server`提供的转换服务进行查询, 获得需要的数值. 这部分同时还需要和redis等缓冲配合，从而调高效率，减少远程调用。

## 如果在应用中使用
1. 配置 `raw/config/app.yml`

	```yaml
	rpc:
	  unitconv:
	    url: http://unit-conv.xxx.xxx/api
	```

2. 配置 `raw/config/cache.yml`

	```yaml
	unitconv:
	  driver: Redis
	  options:
	    key_prefix: unitconv-
	    servers:
	      default:
		     host: 127.0.0.1
		     port: 6379
	```

3. 代码中使用

	```php
	$conv = \Gini\Unit\Conversion::of('cas/64-17-5');
	$gram = $conv->from('100ml')->to('g');
	list($value, $unit) = $conv->parse('100ml');
	$units = $conv->getUnits();
	```

## 命令行
### 转换单位

```bash
gini unit-conv convert object=liquid from=100mg to=l
```

### 列出支持的单位

```bash
gini unit-conv list-units
```