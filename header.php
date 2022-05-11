<div class="navbar navbar-default navbar-fixed-top wiki_header" role="navigation">
    <div>
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <div class="imgNavbar">
                <a href="<?=$module->getUrl("browser.php?NOAUTH")?>" style="text-decoration: none;float:left">
                    <?php if($settings['des_logo'] != ""){?>
                   <?=\Vanderbilt\DataModelBrowserExternalModule\printFile($module,$settings['des_logo'],'logo')?>
                    <?php } ?>
                </a>

                <?php
                $style= "";
                if($settings['des_logo'] == ""){
                    $style = "padding-left:20px;padding-bottom: 15px";
                }
                ?>
                <a href="<?=$module->getUrl("browser.php?NOAUTH")?>" style="text-decoration: none;float:left;<?=$style?>" class="hub_header_title">
                    <span class=""><?=$settings['des_doc_title']?></span>
                </a>
            </div>
        </div>
    </div>
</div>