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
                   <?=printFile($module,$settings['des_logo'],'logo')?>
                </a>

                <a href="<?=$module->getUrl("browser.php?NOAUTH")?>" style="text-decoration: none;float:left" class="hub_header_title">
                    <span class=""><?=$settings['des_doc_title']?></span>
                </a>
            </div>
        </div>
    </div>
</div>