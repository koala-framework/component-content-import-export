    # PO
    ./vendor/bin/component-content-import-export export --component-id=root | ./vendor/bin/component-content-import-export convert:to-po > out.po
    ./vendor/bin/component-content-import-export convert:from-po < out.po | ./vendor/bin/component-content-import-export import
    
    # Xliff
    ./vendor/bin/component-content-import-export export --component-id=root | ./vendor/bin/component-content-import-export convert:to-xliff -l de-DE > out.xlf
    ./vendor/bin/component-content-import-export convert:from-xliff < out.xlf | ./vendor/bin/component-content-import-export import
    
    On convert:to-xliff "-l" or "--source-lang" can be used to define the source-language attribute for the xliff-document. Without the standard "de-AT" is used.
    
