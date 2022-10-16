You can use Active Query modifier locally:

```php
class YourModel extends BaseModel
{

    // Every modifier must start with aqModify, accept DB\AQ as parameter
    public static function aqModifyWithSomeColumn(DB\AQ $AQ)
    {
        $AQ->select("*, 'someVal' as someColumn");
    }
    
}
// then you can call modifier
YourModel::findAdv()->modify('withSomeColumn')->all();
```

Parameterized modifiers

```php
class YourModel extends BaseModel
{

    public static function aqModifyOnlyActive(DB\AQ $AQ, bool $isActive)
    {
        $AQ->where(['active' => $isActive]);
    }
    
}
// then you can call modifier
YourModel::findAdv()->modify('onlyActive', true)->all();
```

Also, you can call modifiers by default every time you call find* on model:

```php
class YourModel extends BaseModel
{

    public static function aqModifyWithSomeColumn(DB\AQ $AQ)
    {
        $AQ->select("*, 'someVal' as someColumn");
    }
    
    public static function aqModifiersDefault(): array
    {
        return ['withSomeColumn'];
    }
    
}
// withSomeColumn modifier will be applied
YourModel::findAdv()->all();
```
