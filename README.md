    # PO
    ./vendor/bin/component-content-import-export export --component-id=root | ./vendor/bin/component-content-import-export convert:to-po > out.po
    ./vendor/bin/component-content-import-export convert:from-po < out.po | ./vendor/bin/component-content-import-export import --subrootId=root
    
    When converting from PO to the importable format --reference-file=fileName or -r filename can be used to provide a different file for references. When a msgid doesnt exist in the referencefile it gets skipped.
    If no file is provided, input-file is used for references.
    
    # Xliff
    ./vendor/bin/component-content-import-export export --component-id=root | ./vendor/bin/component-content-import-export convert:to-xliff -l de-DE > out.xlf
    ./vendor/bin/component-content-import-export convert:from-xliff < out.xlf | ./vendor/bin/component-content-import-export import --subrootId=root
    
    On convert:to-xliff "-l" or "--source-lang" can be used to define the source-language attribute for the xliff-document. Without the standard "de-AT" is used.
    
    # Translating for Trl-Components
    ./vendor/bin/component-content-import-export export --component-id=root-master --isTrl | ./vendor/bin/component-content-import-export convert:to-po > out.po
    ./vendor/bin/component-content-import-export convert:from-po < out.po | ./vendor/bin/component-content-import-export import --subrootId=root-trl --isTrl
