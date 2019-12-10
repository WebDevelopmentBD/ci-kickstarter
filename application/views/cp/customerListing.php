<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <i class="fa fa-address-card" aria-hidden="true"></i> Client Management
        <small>Add, Edit, Delete</small>
      </h1>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-xs-12 text-right">
                <div class="form-group">
                    <a class="btn btn-primary" href="<?php echo base_url();?>cp/customerEdit/0"><i class="fa fa-plus"></i> Add New</a>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
              <div class="box">
                <div class="box-header">
                    <h3 class="box-title">Customer List</h3>
                    <div class="box-tools">
                        <form action="<?php echo base_url();?>cp/customerListing" method="POST" id="searchList">
                            <div class="input-group">
                              <input type="text" name="searchText" value="<?php echo $searchText;?>" class="form-control input-sm pull-right" style="width: 150px;" placeholder="Search"/>
                              <div class="input-group-btn">
                                <button class="btn btn-sm btn-default searchList"><i class="fa fa-search"></i></button>
                              </div>
                            </div>
                        </form>
                    </div>
                </div><!-- /.box-header -->
                <div class="box-body table-responsive no-padding">
                  <table class="table table-hover">
                    <?php //exit('<pre>'.print_r( $thead, TRUE));
					//exit('<pre>'.print_r( $this->cruds->{'sale_profiles'}->get('nickname','id=2'), TRUE));
					?>
                    <tr>
                      <th>Ledger Id</th>
                      <th>Mode</th>
                      <th>Rtype</th>
                      <th>Name</th>
                      <th>Nickname</th>
                      <!--th>Father Name</th-->
                      <!--th>Mother Name</th-->
                      <!--th>Spouse Name</th-->
                      <th>Occupation</th>
                      <th>D.O.B.</th>
                      <th>NID</th>
                      <th>Tin</th>
                      <th>Phone</th>
                      <th>Email</th>
                      <th>Website</th>
                      <th>Address</th>
                      <th>Remark</th>
                      <th>Reference</th>
                      <th>Company</th>
                      <th>Company Phone</th>
                      <th>Addedby</th>
                      <th>Entrance</th>
                      <th class="text-center">Action</th>
                    </tr>
                    <?php
					$coNames = array();
					$owner = array(
					  0 => 'Cancel',
					  1 => 'Own',
					  2 => 'Shared',
					  3 => 'Agent'
					);
                    foreach($dataset as $record)
                    {
						//isset($coNames[ $record->pid ]) or
						//$coNames[ $record->pid ] = $this->cruds->{'sale_profiles'}->get('nickname','`id`='.$record->pid);
					?>
                    <tr data-id="<?php echo $record->id;?>">
                        <td><?php echo $record->ledger_id;?></td>
                        <td><?php echo $record->mode;?></td>
                        <td><?php echo $record->rtype;?></td>
                        <td><?php echo $record->name;?></td>
                        <td><?php echo $record->nickname;?></td>
                        <td><?php echo $record->occupation;?></td>
                        <td><?php echo $record->dob;?></td>
                        <td><?php echo $record->nid;?></td>
                        <td><?php echo $record->tin;?></td>
                        <td><?php echo $record->phone;?></td>
                        <td><?php echo $record->email;?></td>
                        <td><?php echo $record->website;?></td>
                        <td><?php echo $record->address;?></td>
                        <td><?php echo $record->remark;?></td>
                        <td><?php echo $record->reference;?></td>
                        <td><?php echo $record->company;?></td>
                        <td><?php echo $record->company_phone;?></td>
                        <td><?php echo $record->addedby;?></td>
                        <td><?php echo $record->entrance;?></td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-info" href="./branchEdit/<?php echo $record->id; ?>" title="Edit"><i class="fa fa-pencil"></i></a>
                            <a class="btn btn-sm btn-danger deleteUser" href="#" data-i="<?php echo $record->id;?>" title="Delete"><i class="fa fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php }?>
                  </table>
                  
                </div><!-- /.box-body -->
                <div class="box-footer clearfix">
                    <?php echo $this->pagination->create_links();?>
                </div>
              </div><!-- /.box -->
            </div>
        </div>
    </section>
</div>
<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/common.js" charset="utf-8"></script>
<script type="text/javascript">
    jQuery(document).ready(function(){
        jQuery('ul.pagination li a').click(function(e){
            e.preventDefault();            
            var link = jQuery(this).get(0).href;            
            var value = link.substring(link.lastIndexOf('/') + 1);
            jQuery("#searchList").attr("action", baseURL + "branchListing/" + value);
            jQuery("#searchList").submit();
        });
    });
</script>
