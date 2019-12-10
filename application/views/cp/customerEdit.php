<?php
/*
$userId = $userInfo->uid;
$name   = $userInfo->name;
$email  = $userInfo->email;
$mobile = $userInfo->mobile;
$roleId = $userInfo->roleid;
*/

//die(print_r( $record, TRUE ));
?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <i class="fa fa-users"></i> Client Profile
        <small>Add / Edit</small>
      </h1>
    </section>
    
    <section class="content">
    
        <div class="row">
            <!-- left column -->
            <div class="col-md-8">
              <!-- general form elements -->
                
                <div class="box box-primary">
                    <div class="box-header">
                        <h3 class="box-title">Customer Details - <?php echo $record? 'Edit':'New';?></h3>
                    </div><!-- /.box-header -->
                    <!-- form start -->
                    
                    <?php if( $record ){?>
                    <form action="<?php echo base_url();?>branchEdit" method="post" id="editUser" role="form">
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-6">                                
                                    <div class="form-group">
                                        <label for="fname">Full Name</label>
                                        <input type="text" class="form-control" id="fname" placeholder="Full Name" name="fname" value="<?php echo $name;?>" maxlength="128">
                                        <input type="hidden" value="<?php echo $userId;?>" name="userId" id="userId" />    
                                    </div>
                                    
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email address</label>
                                        <input type="email" class="form-control" id="email" placeholder="Enter email" name="email" value="<?php echo $email;?>" maxlength="128">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Password</label>
                                        <input type="password" class="form-control" id="password" placeholder="Password" name="password" maxlength="20">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cpassword">Confirm Password</label>
                                        <input type="password" class="form-control" id="cpassword" placeholder="Confirm Password" name="cpassword" maxlength="20">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mobile">Mobile Number</label>
                                        <input type="text" class="form-control" id="mobile" placeholder="Mobile Number" name="mobile" value="<?php echo $mobile;?>" maxlength="10">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="role">Role</label>
                                        <select class="form-control" id="role" name="role">
                                            <option value="0">Select Role</option>

                                        </select>
                                    </div>
                                </div>    
                            </div>
                        </div> <!-- .box-body -->
    
                        <div class="box-footer">
                            <input type="submit" class="btn btn-primary" value="Submit" />
                            <input type="reset" class="btn btn-default" value="Reset" />
                        </div>
                    </form>
                    <?php }else{?>
                    <h3 class="<?php echo 'hide';?>">Add New Record</h3>
                    <form action="<?php echo base_url();?>branchEdit" method="post" id="editUser" role="form">
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-6">                                
                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" class="form-control" placeholder="Full Name" name="name" maxlength="128">
                                    </div>
                                    
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email address</label>
                                        <input type="email" class="form-control" id="email" placeholder="Enter email" name="email" value="<?php echo $email;?>" maxlength="128">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Password</label>
                                        <input type="password" class="form-control" id="password" placeholder="Password" name="password" maxlength="20">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cpassword">Confirm Password</label>
                                        <input type="password" class="form-control" id="cpassword" placeholder="Confirm Password" name="cpassword" maxlength="20">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mobile">Mobile Number</label>
                                        <input type="text" class="form-control" id="mobile" placeholder="Mobile Number" name="mobile" value="<?php echo $mobile;?>" maxlength="10">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="role">Role</label>
                                        <select class="form-control" id="role" name="role">
                                            <option value="0">Select Role</option>

                                        </select>
                                    </div>
                                </div>    
                            </div>
                        </div> <!-- .box-body -->
    
                        <div class="box-footer">
                            <input type="submit" class="btn btn-primary" value="Submit" />
                            <input type="reset" class="btn btn-default" value="Reset" />
                        </div>
                    </form>
                    <?php }?>
                </div>
            </div>
            <div class="col-md-4">
                <?php
				$this->load->helper('form');
				if( $this->session->flashdata('error') ){?>
                <div class="alert alert-danger alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <?php echo $this->session->flashdata('error'); ?>                    
                </div>
                <?php }
		 		if($this->session->flashdata('success')){?>
                <div class="alert alert-success alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <?php echo $this->session->flashdata('success'); ?>
                </div>
                <?php } ?>
                
                <div class="row">
                    <div class="col-md-12">
                        <?php echo validation_errors(
						  '<div class="alert alert-danger alert-dismissable">',
						  ' <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>'
						);?>
                    </div>
                </div>
            </div>
        </div>    
    </section>
</div>

<script src="<?php echo base_url();?>assets/js/editUser.js" type="text/javascript"></script>