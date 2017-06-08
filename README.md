    ./vendor/bin/component-content-import-export export --component-id=root | ./vendor/bin/component-content-import-export convert:to-po > out.po
    ./vendor/bin/component-content-import-export convert:from-po < out.po | ./vendor/bin/component-content-import-export import
