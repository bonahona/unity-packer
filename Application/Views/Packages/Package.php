<?php /** @var $this PackagesControllerController */ ?>

<h1><?php echo $this->Title;?></h1>
<div class="row">
    <div class="col-lg-12">
        <?php foreach($Logs as $log):?>
            <div class="row">
                <div class="col-lg-12">
                    <?php echo $log->hash;?>
                </div>
                <div class="col-lg-12">
                    <?php echo $log->message;?>
                </div>
                <div class="col-lg-12">
                    <a href="<?php echo $this->GetCommitLink($log);?>" download="<?php echo $this->GetCommitFileName($log);?>" class="btn btn-md btn-default">Get</a>
                </div>
            </div>
        <?php endforeach;?>
    </div>
</div>