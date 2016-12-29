<?php
/**	
*	File Name 			: 	shipmentController.php
*	File Description	:	To Shipment Details 
*	Created Date 		:	10- DEC - 2014
*	Modified Date 		:	01- JUNE - 2015
*	Company				:	iExemplar
*	Created By			:	Manthiriyappan A
*	Contributors		:	Manthiriyappan A
**/
class ShipmentController extends BaseController {
	
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function allShipmentList()
	{

		$shipment_list = DB::select(DB::raw("select ord.ORDER_ID,ord.First_Name,ord.Last_Name,
			ord.Order_Date,oitem.Order_Item,oitem.Order_Qty,pro.Product_Name FROM Orders AS ord
			INNER JOIN OrderItems AS oitem ON oitem.Order_Id=ord.Order_Id
			INNER JOIN Products AS pro ON pro.Product_Id=oitem.Order_Item"));
		return Response::json(array('response' => $shipment_list));
	}
	
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function startedShipmentList(){
		$requested_ch_right ="AllowShipmentView";
		$usergroup = User::find(Auth::id())->usergroup()->first();
		$started_shipment_list = DB::select(DB::raw("SELECT ore.id as Order_Id,ore.Order_Date,jstatus.Status_Name,
			CAR.Carrier_Name,CARSN.CarrierService_Name,ore.id as Order_Id,ship.id as Shipment_Id, job.Job_Type_Id, job.Channel_id ,
			job.Job_Status_Id, ship.Date_Of_Shipment,ship.ShipmentCost,ship.Carrier_Id,
			IF(ship.SignatureRequired='1','Required', 'Not Required') AS SignatureRequired,
			ship.CarrierService_Id,ship.Channel_Name,ship.Warehouse_Name,ship.RecipientName,
			IF(ship.EmailNotification='1','Notify', 'Do Not Notify') AS EmailNotification,
			ship.ShipAddress1,ship.ShipAddress2,ship.ShipCity,ship.ShipState,ship.ShipZip,
			ship.ShipCountry,ship.ShipEmail,ship.ShippingAccount,ship.TrackingNumber,ship.ShipmentCost,
			ship.ActualShipmentCost,ship.AdditionalNotes FROM `shipments` AS ship
			LEFT JOIN Jobs AS job ON job.id=ship.Job_Id
			LEFT JOIN Orders AS ore ON ore.Job_Id = job.Parent_Job_Id 
			LEFT JOIN Carrier  AS CAR ON CAR.id = ship.Carrier_Id
			LEFT JOIN CarrierServices AS CARSN ON CARSN.id = ship.CarrierService_Id
			INNER JOIN job_type_status AS jstatus ON jstatus.id=job.Job_Status_Id group by ship.id
			 HAVING job.Channel_id in(select ugchr.channel_id
										from user_group_channel_right ugchr, channel_right chr
										  where ugchr.userGroupID = ".$usergroup->id."
				 								and chr.channel_right_name = '".$requested_ch_right."')"));
		return Response::json(array('response' => $started_shipment_list));
	}		
	
	public function orderToShipmentList(){			
	$order_to_shipment_list = DB::select(DB::raw("SELECT ore.Order_Id,ore.Order_Date,jstatus.Status_Name,
		CAR.Carrier_Name,CARSN.CarrierService_Name,ore.Order_Id,ship.Shipment_Id, job.Job_Type_Id,
		job.Job_Status_Id, ship.Date_Of_Shipment,ship.ShipmentCost,ship.Carrier_Id,ship.CarrierService_Id,
		ship.Channel_Name,ship.Warehouse_Name,ship.RecipientName,ship.ShipAddress1,ship.ShipAddress2,
		ship.ShipCity,ship.ShipState,ship.ShipZip,ship.ShipCountry,ship.ShipEmail,ship.ShippingAccount,
		ship.TrackingNumber,ship.ShipmentCost,ship.ActualShipmentCost,ship.AdditionalNotes FROM `Shipments` AS ship 
		LEFT JOIN Jobs AS job ON job.Job_Id=ship.Job_Id 
		LEFT JOIN Orders AS ore ON ore.Job_Id = job.Parent_Job_Id 
		LEFT JOIN Carrier AS CAR ON CAR.Carrier_Id = ship.Carrier_Id
		LEFT JOIN CarrierServices AS CARSN ON CARSN.CarrierService_Id = ship.CarrierService_Id
		INNER JOIN JobTypeStatus AS jstatus ON jstatus.JobTypeStatus_Id=job.Job_Status_Id 
		WHERE job. Job_Type_Id=".'"4"'." AND job.Job_Status_Id=".'"4"'." AND job.Active=".'"1"'." AND ore.Order_Id=".Input::json('id'))); 
	return Response::json(array('response' => $order_to_shipment_list)); 
	}
	
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function showPartiallShipmentDetails(){	
	$partial_shipment_list = DB::select(DB::raw("SELECT ORD.*, OREI.id as Order_Item_Id, OREI.Order_Qty,
			OREI.Balance_Qty, PRO.Product_Name FROM orders AS ORD 
			LEFT JOIN orderitems AS OREI ON OREI.Order_Id = ORD.id
			LEFT JOIN products AS PRO ON PRO.id = OREI.Order_Item
			WHERE (OREI.Order_Qty != OREI.Balance_Qty AND OREI.Balance_Qty != "."'0'"." AND OREI.Order_Id = " . Input::json('id').")"));
		return Response::json(array('response' => $partial_shipment_list));
	}
	
	/*
	*	Get Channel and Id form Channel Table
	*
	**/
	public function getShipingChannel(){
		// Query To Fetch Products Details
		$channel_list = DB::table('channels')->get();
		return Response::json(array('response' => $channel_list));
	}

	/*
	*	Get Product and Id form Product Table
	*
	**/
	public function getShipingProduct(){
		// Query To Fetch Products Details
		$product_list = DB::table('products')->get();
		return Response::json(array('response' => $product_list));
	}
	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function storeShipment(){

		// Insert New jobs Details in jobs Table
		$id = Input::json('channel_name');
		$channel_name_tbl = $this->getChannelName($id);
		$id = Input::json('warehouse');
		$warehouse_name_tbl = $this->getWarehouseName($id);
		$jobs_dtl = new Jobs; 
	 	$jobs_dtl->Parent_Job_Id = Input::json('p_jobid'); 
		$jobs_dtl->Channel_Id = Input::json('channel_name');
		$jobs_dtl->Job_Type_Id = "4";
		$jobs_dtl->Job_Status_Id = "4";
		$jobs_dtl->ACTIVE = 1;
		$jobs_dtl->save();
		
		// Insert New Shipments Details in Shipments Table
		$shipment_dtl = new Shipments;
		$shipment_dtl->Job_Id =$jobs_dtl->id;
		$shipment_dtl->Date_Of_Shipment =  Input::json('date_of_shipment');
		$shipment_dtl->Channel_Name = $channel_name_tbl;
		$shipment_dtl->Warehouse_Id = Input::json('warehouse');
		$shipment_dtl->Warehouse_Name = $warehouse_name_tbl;
		$shipment_dtl->Carrier_Id = Input::json('carrier_name');
		$shipment_dtl->CarrierService_Id = Input::json('carrier_service_name');
		$shipment_dtl->RecipientName   = Input::json('recipient_name');
		$shipment_dtl->ShipAddress1	 = Input::json('shippingaddress');
		$shipment_dtl->ShipAddress2	 = Input::json('shippingaddress2');
		$shipment_dtl->ShipCountry =  Input::json('shipcountry');
		$shipment_dtl->ShipState	 = Input::json('shipstate');
		$shipment_dtl->ShipCity   = Input::json('shipcity');
		$shipment_dtl->ShipZip	 = Input::json('shipzip');
		$shipment_dtl->ShipEmail	 = Input::json('ship_email');
		$shipment_dtl->ShippingAccount	 = Input::json('shipping_account');
		$shipment_dtl->TrackingNumber   = Input::json('tracking_number');
		$shipment_dtl->ShipmentCost	 = Input::json('shipping_cost');
		$shipment_dtl->ActualShipmentCost = Input::json('actual_shipment_cost');
		$shipment_dtl->SignatureRequired   = Input::json('signature_required');
		$shipment_dtl->EmailNotification	 = Input::json('email_notification');
		$shipment_dtl->TrackingNumber   = Input::json('tracking_number');
		$shipment_dtl->AdditionalNotes	 = Input::json('additional_notes');
		$shipment_dtl->ACTIVE = 1;
		$shipment_dtl->save();
		
		//Particular Shipment Qty Details		
		$order_item_count = count(Input::json('order_item_id'));
			for($i=0; $i<$order_item_count; $i++){					
				$inventory_list = $this->getBalanceQty(Input::json('order_item_id')['order_item_id'.$i]);
				$prod_list = $this->getProductDetailsByProductId(Input::json('order_item')['order_item'.$i]);
				$itm_id =  Input::json('order_item')['order_item'.$i];
				$channel_id = Input::json('channel_name');
				$warehouse_ids =  Input::json('warehouse');
				$inventory_id =  $this->getInventoryIDByProdId($itm_id,$warehouse_ids,$channel_id);
				$inv_id = $inventory_id[0]->InventoryItem_Id;
				$stock_count = $inventory_id[0]->Stock_Count;									
				$order_shb_item_dtl = new ShipmentItems;
				$order_shb_item_dtl->Shipment_Id =$shipment_dtl->id;
				$order_shb_item_dtl->Inventory_Item_Id	= $inv_id; 
				$order_shb_item_dtl->ItemName = $prod_list[0]->Product_Name;
				$order_shb_item_dtl->Order_Qty =Input::json('order_qty')['order_qty'.$i];
				$order_shb_item_dtl->Shipment_Qty = Input::json('shipment_qty')['shipment_qty'.$i];
				$order_shb_item_dtl->Ship_Balance_Qty = Input::json('shipment_qty')['shipment_qty'.$i];
				$order_shb_item_dtl->save();
				$bal_qty = ((Input::json('avilable_qty')['avilable_qty'.$i]) - (Input::json('shipment_qty')['shipment_qty'.$i]));
				$order_bal_qty = ((Input::json('balance_qty')['balance_qty'.$i]) - (Input::json('shipment_qty')['shipment_qty'.$i]));
				$order_item_id = ((Input::json('order_item_id')['order_item_id'.$i]));
				$this->updateBalanceQtyOrderItems($order_bal_qty, $order_item_id);								
				$this->updateStockCountInventoryItems($bal_qty, $inv_id);			
								
				$inv_log_dtl = new InventoryItemLog;
				$inv_log_dtl->Job_Id = $jobs_dtl->id;
				$inv_log_dtl->InventoryItem_Id =  $inv_id;
				$inv_log_dtl->Product_Id =Input::json('order_item')['order_item'.$i];
				$inv_log_dtl->Channel_Id = Input::json('channel_name');
				$inv_log_dtl->Warehouse_Id = Input::json('warehouse');
				$inv_log_dtl->Stock_Count =  $bal_qty;
				$inv_log_dtl->Re_Order_Level = "0";
				$inv_log_dtl->Notes = "";
				$inv_log_dtl->Reason = "";
				$inv_log_dtl->LastCycleCount = "0";
				$inv_log_dtl->LastAdjustmentCount = "0";	
				$inv_log_dtl->Active = 1;
				$inv_log_dtl->save();
			}			
		return Response::json(array('Response'=> $shipment_dtl->id, 'flash' => 'Save Successfully!!!!!'));
	}
  	
	public function updateBalanceQtyOrderItems($order_bal_qty, $order_item_id){
			$update_order_item_details = DB::select(DB::raw('UPDATE OrderItems SET Balance_Qty="'.$order_bal_qty.'" WHERE Order_Item_Id = '.$order_item_id )); 			
	} 
			
	public function updateShipmentStatus(){	
		$update_job_details = DB::select(DB::raw('UPDATE jobs SET `Job_Status_Id` = '. Input::json('status_id'). '  where Job_Id = '. Input::json('jobid'))); 
		return Response::json(array('response' => "Succesfully Updated!!!"));
	}		
	public function editShipmentDetails(){
		$old_shipment_item = Input::json('old_shipment_qty');	
		$new_shipment_item = Input::json('shipment_qty');
		$result=array_diff($old_shipment_item,$new_shipment_item);
		if(count($result)== "0"){
				$update_job_details = DB::select(DB::raw('update jobs set `Job_Status_Id` ='. "'" .Input::json('status'). "'". ' where Job_Id = ' . Input::json('jobid'))); 
				$update_shipment_details = DB::select(DB::raw('update shipments set `Date_Of_Shipment` ='. "'" .Input::json('date_of_shipment'). "'" .',
				 `Warehouse_Name` ='. "'" . Input::json('warehouse')."'" .',
				 `TrackingNumber` ='. "'" . Input::json('tracking_number')."'" .',
				 `RecipientName` ='. "'" .Input::json('recipient_name'). "'" .',
				 `ShipAddress1` ='. "'" . Input::json('shippingaddress')."'" .',
				 `ShipAddress2` ='. "'" .Input::json('shippingaddress2'). "'" .',
				 `ShipCountry` ='. "'" . Input::json('shipcountry')."'" .',
				 `ShipState` ='. "'" .Input::json('shipstate'). "'" .',
				 `ShipCity` ='. "'" . Input::json('shipcity')."'" .',
				 `ShipZip` ='. "'" .Input::json('shipzip'). "'" .',
				 `ShipEmail` ='. "'" . Input::json('ship_email')."'" .',
				 `ShippingAccount` ='. "'" .Input::json('shipping_account'). "'" .',
				 `TrackingNumber` ='. "'" . Input::json('tracking_number')."'" .',
				 `ShipmentCost` ='."'" .Input::json('shipping_cost')."'" .',
				 `ActualShipmentCost` ='. "'" . Input::json('actual_shipment_cost')."'" .',
				 `Carrier_Id` ='. "'" . Input::json('carrier_name')."'" .',
				 `CarrierService_Id` ='. "'" . Input::json('carrier_service_name') ."'" .',
				 `SignatureRequired` ='. "'" .Input::json('signature_required'). "'" .',
				 `EmailNotification` ='. "'" . Input::json('email_notification')."'" .',
				 `AdditionalNotes` ='. "'" . Input::json('additional_notes')."'" .'
				  where Shipment_Id = ' . Input::json('shipmentid')));		
				
			return Response::json(array('response' => "Succesfully Updated!!!"));	
		}else{
			$delete_shipment_box_details = DB::select(DB::raw('UPDATE ShipmentsBoxes SET `ACTIVE` = "0" WHERE Shipment_Id = ' . Input::json('shipmentid'))); 
			//$delete_shipment_box_item_details = DB::select(DB::raw('UPDATE ShipmentsBoxItems SET `ACTIVE` = "0" WHERE Shipment_Box_Id = ' . Input::json('shipment_box_id'))); 
			$update_job_details = DB::select(DB::raw('update jobs set `Job_Status_Id` ='. "'" .Input::json('status'). "'". ' where Job_Id = ' . Input::json('jobid'))); 
				$update_shipment_details = DB::select(DB::raw('update shipments set `Date_Of_Shipment` ='. "'" .Input::json('date_of_shipment'). "'" .',
				 `Warehouse_Name` ='. "'" . Input::json('warehouse')."'" .',
				 `TrackingNumber` ='. "'" . Input::json('tracking_number')."'" .',
				 `RecipientName` ='. "'" .Input::json('recipient_name'). "'" .',
				 `ShipAddress1` ='. "'" . Input::json('shippingaddress')."'" .',
				 `ShipAddress2` ='. "'" .Input::json('shippingaddress2'). "'" .',
				 `ShipCountry` ='. "'" . Input::json('shipcountry')."'" .',
				 `ShipState` ='. "'" .Input::json('shipstate'). "'" .',
				 `ShipCity` ='. "'" . Input::json('shipcity')."'" .',
				 `ShipZip` ='. "'" .Input::json('shipzip'). "'" .',
				 `ShipEmail` ='. "'" . Input::json('ship_email')."'" .',
				 `ShippingAccount` ='. "'" .Input::json('shipping_account'). "'" .',
				 `TrackingNumber` ='. "'" . Input::json('tracking_number')."'" .',
				 `ShipmentCost` ='."'" .Input::json('shipping_cost')."'" .',
				 `ActualShipmentCost` ='. "'" . Input::json('actual_shipment_cost')."'" .',
				 `Carrier_Id` ='. "'" . Input::json('carrier_name')."'" .',
				 `CarrierService_Id` ='. "'" . Input::json('carrier_service_name') ."'" .',
				 `SignatureRequired` ='. "'" .Input::json('signature_required'). "'" .',
				 `EmailNotification` ='. "'" . Input::json('email_notification')."'" .',
				 `AdditionalNotes` ='. "'" . Input::json('additional_notes')."'" .'
				  where Shipment_Id = ' . Input::json('shipmentid')));		

				$shipment_item_count = count(Input::json('order_item'));	
				for($i=0; $i < $shipment_item_count; $i++){
					if(Input::json('old_shipment_qty')['old_shipment_qty'.$i] > Input::json('shipment_qty')['shipment_qty'.$i]){
						$new_balance_qty = Input::json('old_shipment_qty')['old_shipment_qty'.$i] - Input::json('shipment_qty')['shipment_qty'.$i];
						$new_balance_qty = Input::json('balance_qty')['balance_qty'.$i] + $new_balance_qty;
						$order_balance_qty =Input::json('old_shipment_qty')['old_shipment_qty'.$i] - Input::json('shipment_qty')['shipment_qty'.$i];
						$new_order_balance_qty = Input::json('old_balance_qty')['old_balance_qty'.$i] + $order_balance_qty;
						$update_order_details = DB::select(DB::raw('UPDATE orderitems set `Balance_Qty` ='. $new_order_balance_qty .' where Order_Item_Id = ' . Input::json('order_item_id')['order_item_id'.$i] )); 
						$update_shipment_item_details = DB::select(DB::raw('UPDATE ShipmentItems set `Shipment_Qty` = '. "'" . Input::json('shipment_qty')['shipment_qty'.$i]."'" .' ,`Ship_Balance_Qty` ='. Input::json('shipment_qty')['shipment_qty'.$i] .' where Shipment_Item_Id = ' . Input::json('shipment_item_id')['shipment_item_id'.$i])); 	
						$avilable_stock_counts = Input::json('avilable_stock_count')['avilable_stock_count'.$i] + $order_balance_qty;
						$update_inv_stock_count = DB::select(DB::raw('UPDATE InventoryItems set `Stock_Count` ='. $avilable_stock_counts .' where InventoryItem_Id = ' . Input::json('inv_item_id')['inv_item_id'.$i].' AND Channel_Id ='. Input::json('channel_name').' AND Warehouse_Id ='. Input::json('warehouse_id') )); 	
						
						$inv_log_dtl = new InventoryItemLog;
						$inv_log_dtl->Job_Id = Input::json('jobid');
						$inv_log_dtl->InventoryItem_Id = Input::json('inv_item_id')['inv_item_id'.$i];
						$inv_id = Input::json('inv_item_id')['inv_item_id'.$i];				
						$inventory_ids =  $this->getProdIdByInvID($inv_id);
						$inv_log_dtl->Product_Id = $inventory_ids[0]->Product_Id;
						$inv_log_dtl->Channel_Id = Input::json('channel_name');
						$inv_log_dtl->Warehouse_Id = Input::json('warehouse_id');
						$inv_log_dtl->Stock_Count =  $avilable_stock_counts;
						$inv_log_dtl->Re_Order_Level = "0";
						$inv_log_dtl->Notes = "";
						$inv_log_dtl->Reason = "";
						$inv_log_dtl->LastCycleCount = "0";
						$inv_log_dtl->LastAdjustmentCount = "0";	
						$inv_log_dtl->Active = 1;
						$inv_log_dtl->Create_ts = date("Y-m-d H:i:s");
						$inv_log_dtl->Modify_ts = date("Y-m-d H:i:s");		
						$inv_log_dtl->save();
					
					}else if(Input::json('old_shipment_qty')['old_shipment_qty'.$i] < Input::json('shipment_qty')['shipment_qty'.$i]){
						$new_balance_qty = Input::json('shipment_qty')['shipment_qty'.$i] - Input::json('old_shipment_qty')['old_shipment_qty'.$i];
						$new_balance_qty = Input::json('balance_qty')['balance_qty'.$i] - $new_balance_qty;
						//$order_balance_qty =Input::json('shipment_qty')['shipment_qty'.$i] - Input::json('old_shipment_qty')['old_shipment_qty'.$i];
						$order_balance_qty =Input::json('old_shipment_qty')['old_shipment_qty'.$i] - Input::json('shipment_qty')['shipment_qty'.$i];
						$new_order_balance_qty = Input::json('old_balance_qty')['old_balance_qty'.$i] - $order_balance_qty ;
						$update_order_details = DB::select(DB::raw('update orderitems set `Balance_Qty` ='. $new_order_balance_qty .' where Order_Item_Id = ' . Input::json('order_item_id')['order_item_id'.$i] )); 							
						$update_shipment_item_details = DB::select(DB::raw('update ShipmentItems set `Shipment_Qty` = '. "'" . Input::json('shipment_qty')['shipment_qty'.$i]."'" .',`Ship_Balance_Qty` ='. Input::json('shipment_qty')['shipment_qty'.$i].' where Shipment_Item_Id = ' . Input::json('shipment_item_id')['shipment_item_id'.$i]));  
						$avilable_stock_counts = Input::json('avilable_stock_count')['avilable_stock_count'.$i] + $order_balance_qty;
						$update_inv_stock_count = DB::select(DB::raw('update InventoryItems set `Stock_Count` ='. $avilable_stock_counts .' where InventoryItem_Id = ' . Input::json('inv_item_id')['inv_item_id'.$i].' AND Channel_Id ='. Input::json('channel_name').' AND Warehouse_Id ='. Input::json('warehouse_id') )); 	
						
						$inv_log_dtl = new InventoryItemLog;
						$inv_log_dtl->Job_Id = Input::json('jobid');
						$inv_log_dtl->InventoryItem_Id = Input::json('inv_item_id')['inv_item_id'.$i];
						$inv_id = Input::json('inv_item_id')['inv_item_id'.$i];	
						$inventory_ids =  $this->getProdIdByInvID($inv_id);
						$inv_log_dtl->Product_Id= $inventory_ids[0]->Product_Id;
						$inv_log_dtl->Channel_Id = Input::json('channel_name');
						$inv_log_dtl->Warehouse_Id = Input::json('warehouse_id');
						$inv_log_dtl->Stock_Count =  $avilable_stock_counts;
						$inv_log_dtl->Re_Order_Level = "0";
						$inv_log_dtl->Notes = "";
						$inv_log_dtl->Reason = "";
						$inv_log_dtl->LastCycleCount = "0";
						$inv_log_dtl->LastAdjustmentCount = "0";	
						$inv_log_dtl->Active = 1;
						$inv_log_dtl->Create_ts = date("Y-m-d H:i:s");
						$inv_log_dtl->Modify_ts = date("Y-m-d H:i:s");		
						$inv_log_dtl->save();						
					}				 				
				} 			
		}
	}

	public function getEditShipmentDetails(){   
		$order_item_details = DB::select(DB::raw("SELECT si.Inventory_Item_Id,si.id AS Shipment_Item_Id,inv.Stock_Count,si.Shipment_Id,si.ItemName, si.Order_Qty,si.Shipment_Qty, si.Ship_Balance_Qty
            FROM ShipmentItems AS si
			LEFT JOIN InventoryItems AS inv ON inv.id = si.Inventory_Item_Id
			WHERE si.Shipment_Id=".Input::json('id')));
			
		$order_shipment_details = DB::select(DB::raw("SELECT ore.id AS Order_Id,ore.Order_Date,oi.ItemName,oi.order_item,
			oi.id AS Order_Item_Id,oi.Balance_Qty, ore.RefOrderId,ship.Date_Of_Shipment,ship.id AS Shipment_Id,job.id AS Job_Id
			 FROM Shipments AS ship
			LEFT JOIN jobs AS job ON job.id = ship.Job_Id
			LEFT JOIN orders AS ore ON ore.Job_Id= job.Parent_Job_Id
			LEFT JOIN orderitems AS oi ON oi.Order_Id = ore.id
		WHERE ship.id=".Input::json('id')));
			
	    $shipment_list = DB::select(DB::raw("SELECT invitem.id AS InventoryItem_Id,shipitem.id AS Shipment_Item_Id,JTS.status_name AS Status_Name,
			pro.Product_Name,shipitem.ItemName,shipitem.Order_Qty,shipitem.Shipment_Qty,
			shipitem.Ship_Balance_Qty,CAR.Carrier_Name,CARSN.CarrierService_Name,ship.id AS Shipment_Id,ship.Date_Of_Shipment,ship.Carrier_Id,
			ship.TrackingNumber,ship.ShipmentCost,ship.Warehouse_Id,ship.AdditionalNotes,ship.CarrierService_Id,
			ship.Channel_Name,ship.Warehouse_Name,ship.RecipientName,ship.ShipAddress1,ship.ShipAddress2,
			ship.ShipCity,ship.ShipState,ship.ShipZip,ship.ShipCountry,ship.ShipEmail,ship.SignatureRequired,ship.EmailNotification,
			ship.ShippingAccount,ship.TrackingNumber,ship.ShipmentCost,ship.ActualShipmentCost,
			ship.AdditionalNotes,job.Channel_Id, job.Job_Status_Id,
			cha.Channel_Name,job.id AS Job_Id FROM shipments AS ship
			JOIN jobs AS job ON job.id=ship.Job_Id
			INNER JOIN channels AS cha ON cha.id=job.Channel_Id
			LEFT JOIN shipmentitems AS shipitem ON shipitem.Shipment_Id=ship.id
			LEFT JOIN inventoryitems AS invitem ON invitem.id=shipitem.Inventory_Item_Id
			INNER JOIN products AS pro ON pro.id = invitem.Product_Id
			LEFT JOIN job_type_status AS JTS ON JTS.id=JOB.Job_Status_Id
			LEFT JOIN carrier  AS CAR ON CAR.id = ship.Carrier_Id
			LEFT JOIN carrierservices AS CARSN ON CARSN.id = ship.CarrierService_Id
		WHERE ship.id=". Input::json('id')));
					
		$shipmentbox_list = DB::select(DB::raw("SELECT * FROM shipmentsboxes WHERE Active=".'"1"'." AND Shipment_Id=".Input::json('id')));
		$shipmentbox_item_list = DB::select(DB::raw("SELECT sbox.id AS Shipment_Box_Id,sitem.Shipment_Box_Id,sitem.Item_Name,sitem.Boxed_Qty,
                                                    sbox.Shipment_Id FROM shipmentsboxitems AS sitem
                                                    LEFT JOIN shipmentsboxes AS sbox ON sbox.id = sitem.Shipment_Box_Id WHERE sbox.Shipment_Id=".Input::json('id')));
		
		$activeshipment_box = DB::select(DB::raw("SELECT shbx.id AS Shipment_Box_Id,shbx.BoxName FROM shipmentsboxes shbx WHERE
			shbx.id NOT IN (SELECT Shipment_Box_Id FROM shipmentsboxitems WHERE Active='1') AND
			shbx.Shipment_Id=".Input::json('id').""));
			
		$channel_list = DB::table('channels')->get();		
		$product_list = DB::table('products')->get();
		$direct_shipment_item = DB::select(DB::raw("SELECT shipitem.ItemName,shipitem.Shipment_Id,
                                                    ship.id AS Shipment_Id,pro.Product_Name,invent.Product_Id, shipitem.Order_Qty,
                                                    shipitem.Shipment_Qty,shipitem.Ship_Balance_Qty FROM ShipmentItems AS shipitem
                                                    LEFT JOIN shipments AS ship ON ship.id = shipitem.Shipment_Id
                                                    LEFT JOIN inventoryitems AS invent ON invent.id = shipitem.Inventory_Item_Id
                                                    LEFT JOIN products AS pro ON pro.id = invent.Product_Id WHERE shipitem.Shipment_id=".Input::json('id')));
		
		$shipmentboxitm_list = DB::select(DB::raw("SELECT * FROM shipmentsboxitems WHERE  Shipment_Box_Id="."'8'"));
		$direct_shipmentbox_list = DB::select(DB::raw("SELECT * FROM shipmentsboxes WHERE Active=".'"1"'." AND Shipment_Id=".Input::json('id')));
		$direct_shipment_item_details =DB::select(DB::raw("SELECT * FROM shipmentsboxitems WHERE Active=".'"1"'." AND Shipment_Box_Id=".Input::json('id')));
				
		return Response::json(array('order_to_shipment'=>$order_shipment_details,'response' => $shipment_list, 'channel' => $channel_list, 'product' => $product_list,
		'order_item_details' => $order_item_details,'shipmentboxitm_list'=> $shipmentboxitm_list,'direct_shipment_item' => $direct_shipment_item,'shipment_box_list'=>$shipmentbox_list,'activebox'=>$activeshipment_box,
		'direct_shipmentbox_list'=>$direct_shipmentbox_list,'direct_shipment_item_details'=>$direct_shipment_item_details,'shipmentbox_item_list' => $shipmentbox_item_list));
	}
	      
	public function deleteShipmentDetails(){
		$delete_shipment_details = DB::select(DB::raw('UPDATE shipments SET `ACTIVE` = "0" WHERE Shipment_Id = ' . Input::json('id'))); 
		return Response::json(array('response' => "Record Deleted Successfully!!"));
	}
	
	public function getShipmentCount(){
//        $all_shipment_list = DB::select(DB::raw("select ORE.id as ORDER_ID, ORE.Order_Date, ORE.Job_Id, OREI.Order_Qty, OREI.Balance_Qty, OREI.id as Order_Item, PRO.Product_Name, JOB.Channel_Id from orders AS ORE
//                                                LEFT JOIN orderitems AS OREI ON OREI.Order_Id=ORE.id
//                                                LEFT JOIN products AS PRO ON PRO.id = OREI.Order_Item
//                                                LEFT JOIN jobs AS JOB ON JOB.id = ORE.Job_Id
//                                                where OREI.Balance_Qty != ".'"0"'." Group By ORE.id"));
        $all_shipment_list = DB::select(DB::raw("SELECT ship.id AS Shipment_Id, job.Job_Type_Id, job.Job_Status_Id,jstatus.status_name AS Status_Name, ship.Date_Of_Shipment,ship.ShipmentCost
                                                  FROM Shipments AS ship
                                                  INNER JOIN Jobs AS job ON job.id=ship.Job_Id
                                                  INNER JOIN job_type_status AS jstatus ON jstatus.id=job.Job_Status_Id"));

        $start_shipment_list = DB::select(DB::raw("SELECT ship.id as Shipment_Id, job.Job_Type_Id, job.Job_Status_Id,jstatus.status_name as Status_Name, ship.Date_Of_Shipment,ship.ShipmentCost
                                                  FROM Shipments as ship
                                                  inner join Jobs as job on job.id=ship.Job_Id
                                                  inner join job_type_status as jstatus on jstatus.id=job.Job_Status_Id
                                                   where Job_Status_Id= "."'4'"));
        $partial_shipment_list = DB::select(DB::raw("select ORD.*, OREI.id as Order_Item_Id
                                                    from orders as ORD left join orderitems as OREI on OREI.Order_Id = ORD.id
                                                     where (OREI.Order_Qty != OREI.Balance_Qty AND OREI.Balance_Qty != "."'0'".") GROUP BY ORD.id"));
        $ready_shipment_list = DB::select(DB::raw("SELECT ship.id as Shipment_Id, job.Job_Type_Id, job.Job_Status_Id,jstatus.status_name as Status_Name, ship.Date_Of_Shipment,ship.ShipmentCost
                                                  FROM Shipments as ship
                                                  inner join Jobs as job on job.id=ship.Job_Id
                                                  inner join job_type_status as jstatus on jstatus.id=job.Job_Status_Id
                                                   where Job_Status_Id= "."'6'"));
        $boxed_shipment_list = DB::select(DB::raw("SELECT ship.id as Shipment_Id, job.Job_Type_Id, job.Job_Status_Id,jstatus.status_name as Status_Name, ship.Date_Of_Shipment,ship.ShipmentCost
                                                   FROM Shipments as ship
                                                   inner join Jobs as job on job.id=ship.Job_Id
                                                   inner join job_type_status as jstatus on jstatus.id=job.Job_Status_Id
                                                   where Job_Status_Id= "."'5'"));
        $shipped_shipment_list = DB::select(DB::raw("SELECT ship.id as Shipment_Id, job.Job_Type_Id, job.Job_Status_Id,jstatus.Status_Name, ship.Date_Of_Shipment,ship.ShipmentCost
                                                     FROM Shipments as ship
                                                     inner join Jobs as job on job.id=ship.Job_Id
                                                     inner join job_type_status as jstatus on jstatus.id=job.Job_Status_Id
                                                     where Job_Status_Id= "."'7'"));
        $cancelled_shipment_list = DB::select(DB::raw("SELECT ship.id as Shipment_Id, job.Job_Type_Id, job.Job_Status_Id,jstatus.Status_Name, ship.Date_Of_Shipment,ship.ShipmentCost
                                                      FROM Shipments as ship
                                                      inner join Jobs as job on job.id=ship.Job_Id
                                                       inner join job_type_status as jstatus on jstatus.id=job.Job_Status_Id
                                                       where Job_Status_Id= "."'8'"));
        $direct_shipment_lists = DB::select(DB::raw("SELECT ship.id as Shipment_Id, job.Job_Type_Id, job.Job_Status_Id,jstatus.Status_Name, ship.Date_Of_Shipment,ship.ShipmentCost
                                                     FROM Shipments as ship inner join Jobs as job on job.id=ship.Job_Id
                                                       inner join job_type_status as jstatus on jstatus.id=job.Job_Status_Id
                                                      where Job_Status_Id= "."'15'"));



        $listChannel = DB::select(DB::raw("SELECT jobs.channel_id,channels.*
                                            FROM shipments
                                            LEFT JOIN jobs
                                            ON jobs.id = shipments.Job_id
                                            LEFT JOIN channels
                                            ON jobs.channel_id = channels.id
                                            GROUP BY jobs.channel_id"));

        $arrayList = array();
        if(count($listChannel)>0){
            for($i=0;$i<count($listChannel); $i++){
                $arrayList[$i] = array();
                $arrayList[$i]['id'] = $listChannel[$i]->id;
                $arrayList[$i]['title'] = $listChannel[$i]->channel_name;
                $arrayList[$i]['count'] = 0;
                $arrayList[$i]['submenus'] = array();
                $listChannelStatus =DB::select(DB::raw("SELECT job_type_status.id,job_type_status.status_name
                                                FROM
                                                   job_type_status
                                                LEFT JOIN job_type
                                                ON job_type.id= job_type_status.job_type_id
                                                WHERE job_type_name='Shipment' and channel_id =".$arrayList[$i]['id']));
                for($j=0; $j<count($listChannelStatus); $j++){
                    $statusID = $listChannelStatus[$j]->id;
                    $channelID = $arrayList[$i]['id'];
                    $listOrderList = DB::select(DB::raw("SELECT ship.id AS Shipment_Id, job.Job_Type_Id, job.Job_Status_Id,jstatus.status_name AS Status_Name, ship.Date_Of_Shipment,ship.ShipmentCost
                                                  FROM Shipments AS ship
                                                  INNER JOIN Jobs AS job ON job.id=ship.Job_Id
                                                  INNER JOIN job_type_status AS jstatus ON jstatus.id=job.Job_Status_Id
                                                  WHERE job.Job_Status_Id =".$statusID));
                    $arrayList[$i]['submenus'][$j] = array();
                    $arrayList[$i]['submenus'][$j]['count'] = count($listOrderList);
                    $arrayList[$i]['count']  =  $arrayList[$i]['count']  + count($listOrderList);
                    $arrayList[$i]['submenus'][$j]['id'] = $listChannelStatus[$j]->id;
                    $arrayList[$i]['submenus'][$j]['title'] = $listChannelStatus[$j]->status_name;
                }
            }
        }
        return Response::json(array('all_shipment_count' =>$all_shipment_list,'start_shipment_count' =>$start_shipment_list, 'partial_shipment_count' =>$partial_shipment_list, 'ready_shipment_count' => $ready_shipment_list, 'boxed_shipment_count'=> $boxed_shipment_list, 'shipped_shipment_count' => $shipped_shipment_list, 'cancelled_shipment_count' => $cancelled_shipment_list,'direct_shipment_count'=>$direct_shipment_lists ,'all_shipment_channel_list' =>$arrayList));
	} 
	
	public function allDirectShipmentList(){
		$direct_shipment_list = DB::select(DB::raw("SELECT ship.Shipment_Id, job.Job_Type_Id,
				job.Job_Status_Id,jstatus.Status_Name, ship.Date_Of_Shipment,ship.ShipmentCost,
				ship.CarrierName,ship.CarrierServiceName,ship.Channel_Name,
				ship.Warehouse_Name,ship.ShipAddress1,ship.ShipAddress2,ship.RecipientName,
				ship.ShipCity,ship.ShipState,ship.ShipZip,ShipCountry,ship.ShipEmail,
				ship.ShippingAccount,ship.TrackingNumber,ship.ActualShipmentCost,
				ship.AdditionalNotes FROM `Shipments` AS ship 
				INNER JOIN Jobs AS job ON job.job_id=ship.job_id 
				INNER JOIN JobTypeStatus AS jstatus ON jstatus.JobTypeStatus_Id=job.Job_Status_Id
				WHERE ship.Active = ".'"1"'." and Job_Status_Id= "."'15'"));
		return Response::json(array('response' => $direct_shipment_list));
	}	
	
	public function getBalanceQty($id){
		$order_balance_qty = DB::select(DB::raw("SELECT Balance_Qty FROM `OrderItems` WHERE Order_Item_Id =".$id));		
		return $order_balance_qty;
	}

	/*
	*	Get Product details form Product Table
	*
	**/
	
	public function getProductDetailsByProductId($id){
		// Query To Fetch Products Details
		$product_id = $id; 
		$product_id_list = DB::select(DB::raw("SELECT Product_Name,Product_Description,Price,RefItemId,SKU,UnitWeight
                                                 	FROM products WHERE id =".$product_id));
		return $product_id_list;
	}	
	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	 
	public function storeDirectShipment(){	
		$id = Input::json('channel_name');
		$channel_name_tbl = $this->getChannelName($id);
		$id = Input::json('warehouse');
		$warehouse_name_tbl = $this->getWarehouseName($id);
		
		// Insert New jobs Details in jobs Table		
		$jobs_dtl = new Jobs;
		$jobs_dtl->Channel_Id = Input::json('channel_name');
		$jobs_dtl->Job_Type_Id = "4";
		$jobs_dtl->Job_Status_Id = "4";
		$jobs_dtl->Active = 1;
		$jobs_dtl->save();
		
		// Insert New Shipments Details in Shipments Table
		$shipment_dtl = new Shipments;
		$shipment_dtl->Job_Id =$jobs_dtl->id;
		$shipment_dtl->Date_Of_Shipment =  Input::json('date_of_shipment');
		$shipment_dtl->Channel_Name = $channel_name_tbl;
		$shipment_dtl->Warehouse_Id = Input::json('warehouse');
		$shipment_dtl->Warehouse_Name = $warehouse_name_tbl;
		$shipment_dtl->Carrier_Id = Input::json('carrier_name');
		$shipment_dtl->CarrierService_Id =Input::json('carrier_service_name');
		$shipment_dtl->RecipientName   = Input::json('recipient_name');
		$shipment_dtl->ShipAddress1	 = Input::json('shippingaddress');
		$shipment_dtl->ShipAddress2	 = Input::json('shippingaddress2');
		$shipment_dtl->ShipCountry =  Input::json('shipcountry');
		$shipment_dtl->ShipState	 = Input::json('shipstate');
		$shipment_dtl->ShipCity   = Input::json('shipcity');
		$shipment_dtl->ShipZip	 = Input::json('shipzip');
		$shipment_dtl->ShipEmail = Input::json('ship_email');		
		$shipment_dtl->ShippingAccount	 = Input::json('shipping_account');
		$shipment_dtl->TrackingNumber   = Input::json('tracking_number');
		$shipment_dtl->ShipmentCost	 = Input::json('shipment_cost');
		$shipment_dtl->SignatureRequired   = Input::json('signature_required');
		$shipment_dtl->EmailNotification	 = Input::json('email_notification');
		$shipment_dtl->ActualShipmentCost = Input::json('actual_shipment_cost');
		$shipment_dtl->AdditionalNotes	 = Input::json('additional_notes');
		$shipment_dtl->Active = 1;
		$shipment_dtl->save();
		
		//Particular Shipment Qty Details		
		$order_item_count = Input::json('item_count');
		$deleted_item_array = array();
		$deleted_item_array = Input::json('deleted_item_list');	
		for($i=1; $i<= $order_item_count; $i++){		
		if (!(in_array($i, $deleted_item_array))) {			
			if(isset(Input::json('order_item')['order_item'.$i])){	
				$prod_list = $this->getProductDetailsByProductId(Input::json('order_item')['order_item'.$i]);
				$itm_id =  Input::json('order_item')['order_item'.$i];
				$channel_id = Input::json('channel_name');
				$warehouse_ids =  Input::json('warehouse');
				$inventory_id =  $this->getInventoryIDByProdId($itm_id,$warehouse_ids,$channel_id);
				$inv_id = $inventory_id[0]->id;
				$stock_count = $inventory_id[0]->Stock_Count;					
				$order_shb_item_dtl = new ShipmentItems;
				$order_shb_item_dtl->Shipment_Id =$shipment_dtl->id;
				$order_shb_item_dtl->Inventory_Item_Id	= $inv_id; 
				$order_shb_item_dtl->ItemName = $prod_list[0]->Product_Name;
				$order_shb_item_dtl->Order_Qty =Input::json('order_qty')['order_qty'.$i];
				$order_shb_item_dtl->Shipment_Qty = Input::json('shipping_qty')['shipping_qty'.$i];
				$order_shb_item_dtl->Ship_Balance_Qty = Input::json('shipping_qty')['shipping_qty'.$i];
				$order_shb_item_dtl->save();
				$bal_qty = ((Input::json('avilable_qty')['avilable_qty'.$i]) - (Input::json('shipping_qty')['shipping_qty'.$i]));
				$this->updateStockCountInventoryItems($bal_qty, $inv_id);	

				$inv_log_dtl = new InventoryItemLog;
				$inv_log_dtl->Job_Id = $jobs_dtl->id;
				$inv_log_dtl->InventoryItem_Id =  $inv_id; 
				$inv_log_dtl->Product_Id =Input::json('order_item')['order_item'.$i];
				$inv_log_dtl->Channel_Id = Input::json('channel_name');
				$inv_log_dtl->Warehouse_Id = Input::json('warehouse');
				$inv_log_dtl->Stock_Count =  $bal_qty;
				$inv_log_dtl->Re_Order_Level = "0";
				$inv_log_dtl->Notes = "";
				$inv_log_dtl->Reason = "";
				$inv_log_dtl->LastCycleCount = "0";
				$inv_log_dtl->LastAdjustmentCount = "0";	
				$inv_log_dtl->Active = 1;
				$inv_log_dtl->save();
			}
		}
		}
		return Response::json(array('Response'=> $shipment_dtl->id,'flash' => 'Save Successfully!!!!!'));
	}	
		
	public function getInventoryIDByProdId($prodid,$warehouse_ids,$channel_id){
	  $inventory_list = DB::select(DB::raw("SELECT id,Stock_Count FROM inventoryitems
											WHERE  Product_Id = ".$prodid.' AND Warehouse_Id = '.$warehouse_ids.' AND Channel_Id='.$channel_id));
	  return $inventory_list;
    }	
	
	public function getProdIdByInvID($inv_id){
	  $inventory_product = DB::select(DB::raw("SELECT Product_Id FROM inventoryitems 
											WHERE  InventoryItem_Id = ".$inv_id)); 
	  return $inventory_product;
    }	
	
	public function updateStockCountInventoryItems($balance_qty, $inv_id){
		$update_order_item_details = DB::select(DB::raw('UPDATE inventoryitems SET Stock_Count="'.$balance_qty.'" WHERE id = '.$inv_id ));
	}
	
	public function getEditDirectShipmentList(){
		$get_direct_shipment_list = DB::select(DB::raw("SELECT ship.id as Shipment_Id, job.Job_Type_Id,
					job.Job_Status_Id,jstatus.status_name as Status_Name, ship.Date_Of_Shipment,ship.ShipmentCost,
					ship.CarrierName,ship.CarrierServiceName,ship.RecipientName,ship.ShipCity,
					ship.ShipState,ship.ShipZip,ShipCountry,ship.ShipEmail,ship.ShippingAccount,
					ship.TrackingNumber,ship.ActualShipmentCost,ship.AdditionalNotes,ship.CarrierName FROM `shipments` as ship
					INNER JOIN jobs AS job ON job.job_id=ship.job_id
					INNER JOIN job_type_status AS jstatus ON jstatus.id=job.Job_Status_Id
					WHERE ship.Active = ".'"1"'." AND Job_Status_Id= "."'15'"));
		return Response::json(array('response' => $get_direct_shipment_list));
	}
	
	public function getEditDirectShipmentDetails(){
		$get_direct_shipment_Details = DB::select(DB::raw("SELECT ship.id AS Shipment_Id,ship.Date_Of_Shipment,ship.CarrierName,ship.CarrierServiceName,ship.TrackingNumber,ship.ShipmentCost,
			ship.AdditionalNotes,ship.Warehouse_Name,ship.RecipientName,ship.ShipAddress1,ShipAddress2,ship.ShipCity,ship.ShipState,
			ship.ShipZip,ship.ShipCountry,ship.ShipEmail,ship.ShippingAccount,ship.TrackingNumber,ship.ActualShipmentCost,
			job.Channel_Id, job.Job_Status_Id, cha.channel_name AS Channel_Name,
			job.id AS Job_Id FROM `shipments` AS ship
			INNER JOIN jobs AS job ON job.id=ship.Job_Id
			INNER JOIN channels AS cha ON cha.id=job.Channel_Id
		WHERE ship.Shipment_Id =". Input::json('id')));
					
		$direct_shipmentbox_list = DB::select(DB::raw("SELECT * FROM shipmentsboxes WHERE Active=".'"1"'." AND Shipment_Id=".Input::json('id')));
		$direct_shipment_item_details =DB::select(DB::raw("SELECT * FROM shipmentsboxItems WHERE Active=".'"1"'." AND Shipment_Box_Id=".Input::json('id')));
		
		return Response::json(array('response' => $get_direct_shipment_Details,'item_details' => $direct_shipment_item_details ,'direct_shipment_box_details' => $direct_shipmentbox_list));
	}		
	
	public function editDirectShipmentDetails(){	
		$old_shipment_item = Input::json('old_shipment_qty');	
		$new_shipment_item = Input::json('shipment_qty');
		$result=array_diff($old_shipment_item,$new_shipment_item);
        echo Input::json('carrier_name');
		if(count($result)== "0"){
				$update_job_details = DB::select(DB::raw('update jobs set `Job_Status_Id` ='. "'" .Input::json('status'). "'". ' where id = ' . Input::json('jobid')));
				$update_shipment_details = DB::select(DB::raw('update shipments set `Date_Of_Shipment` ='. "'" .Input::json('date_of_shipment'). "'" .',
				 `Warehouse_Name` ='. "'" . Input::json('warehouse')."'" .',
				 `TrackingNumber` ='. "'" . Input::json('tracking_number')."'" .',
				 `RecipientName` ='. "'" .Input::json('recipient_name'). "'" .',
				 `ShipAddress1` ='. "'" . Input::json('shippingaddress')."'" .',
				 `ShipAddress2` ='. "'" .Input::json('shippingaddress2'). "'" .',
				 `ShipCountry` ='. "'" . Input::json('shipcountry')."'" .',
				 `ShipState` ='. "'" .Input::json('shipstate'). "'" .',
				 `ShipCity` ='. "'" . Input::json('shipcity')."'" .',
				 `ShipZip` ='. "'" .Input::json('shipzip'). "'" .',
				 `ShipEmail` ='. "'" . Input::json('ship_email')."'" .',
				 `ShippingAccount` ='. "'" .Input::json('shipping_account'). "'" .',
				 `TrackingNumber` ='. "'" . Input::json('tracking_number')."'" .',
				 `ShipmentCost` ='."'" .Input::json('shipping_cost')."'" .',
				 `ActualShipmentCost` ='. "'" . Input::json('actual_shipment_cost')."'" .',
				 `Carrier_Id` ='. "'" . Input::json('carrier_name')."'" .',
				 `CarrierService_Id` ='. "'" . Input::json('carrier_service_name') ."'" .',
				 `SignatureRequired` ='. "'" .Input::json('signature_required'). "'" .',
				 `EmailNotification` ='. "'" . Input::json('email_notification')."'" .',
				 `AdditionalNotes` ='. "'" . Input::json('additional_notes')."'" .'
				  where id = ' . Input::json('shipmentid')));
				
			return Response::json(array('response' => "Succesfully Updated!!!"));	
		}else{
			$delete_shipment_box_details = DB::select(DB::raw('UPDATE ShipmentsBoxes SET `ACTIVE` = "0" WHERE id = ' . Input::json('shipmentid')));
			//$delete_shipment_box_item_details = DB::select(DB::raw('UPDATE ShipmentsBoxItems SET `ACTIVE` = "0" WHERE Shipment_Box_Id = ' . Input::json('shipment_box_id'))); 
			$update_job_details = DB::select(DB::raw('update jobs set `Job_Status_Id` ='. "'" .Input::json('status'). "'". ' where id = ' . Input::json('jobid')));
			$update_shipment_details = DB::select(DB::raw('update shipments set `Date_Of_Shipment` ='. "'" .Input::json('date_of_shipment'). "'" .',
			 `Warehouse_Name` ='. "'" . Input::json('warehouse')."'" .',
			 `TrackingNumber` ='. "'" . Input::json('tracking_number')."'" .',
			 `RecipientName` ='. "'" .Input::json('recipient_name'). "'" .',
			 `ShipAddress1` ='. "'" . Input::json('shippingaddress')."'" .',
			 `ShipAddress2` ='. "'" .Input::json('shippingaddress2'). "'" .',
			 `ShipCountry` ='. "'" . Input::json('shipcountry')."'" .',
			 `ShipState` ='. "'" .Input::json('shipstate'). "'" .',
			 `ShipCity` ='. "'" . Input::json('shipcity')."'" .',
			 `ShipZip` ='. "'" .Input::json('shipzip'). "'" .',
			 `ShipEmail` ='. "'" . Input::json('ship_email')."'" .',
			 `ShippingAccount` ='. "'" .Input::json('shipping_account'). "'" .',
			 `TrackingNumber` ='. "'" . Input::json('tracking_number')."'" .',
			 `ShipmentCost` ='."'" .Input::json('shipping_cost')."'" .',
			 `ActualShipmentCost` ='. "'" . Input::json('actual_shipment_cost')."'" .',
			 `Carrier_Id` ='. "'" . Input::json('carrier_name')."'" .',
			 `CarrierService_Id` ='. "'" . Input::json('carrier_service_name') ."'" .',
			 `SignatureRequired` ='. "'" .Input::json('signature_required'). "'" .',
			 `EmailNotification` ='. "'" . Input::json('email_notification')."'" .',
			 `AdditionalNotes` ='. "'" . Input::json('additional_notes')."'" .'
			  where id = ' . Input::json('shipmentid')));

			$shipment_item_count = count(Input::json('order_item'));		
			for($i=0; $i < $shipment_item_count; $i++){
				if(Input::json('old_shipment_qty')['old_shipment_qty'.$i] > Input::json('shipment_qty')['shipment_qty'.$i]){
					$new_balance_qty = Input::json('old_shipment_qty')['old_shipment_qty'.$i] - Input::json('shipment_qty')['shipment_qty'.$i];
					$new_balance_qty = Input::json('balance_qty')['balance_qty'.$i] + $new_balance_qty;
					$update_shipment_item_details = DB::select(DB::raw('update ShipmentItems set `Shipment_Qty` = '. "'" . Input::json('shipment_qty')['shipment_qty'.$i]."'" .' ,`Ship_Balance_Qty` ='. Input::json('shipment_qty')['shipment_qty'.$i] .' where Shipment_Item_Id = ' . Input::json('shipment_item_id')['shipment_item_id'.$i])); 	
					$order_balance_qty =Input::json('old_shipment_qty')['old_shipment_qty'.$i] - Input::json('shipment_qty')['shipment_qty'.$i];
					$new_order_balance_qty = Input::json('old_balance_qty')['old_balance_qty'.$i] + $order_balance_qty;
					$avilable_stock_counts = Input::json('avilable_stock_count')['avilable_stock_count'.$i] + $order_balance_qty;					
					$update_inv_stock_count = DB::select(DB::raw('update inventoryitems set `Stock_Count` ='. $avilable_stock_counts .' where id = ' . Input::json('inv_item_id')['inv_item_id'.$i].' AND Channel_Id ='. Input::json('channel_name').' AND Warehouse_Id ='. Input::json('warehouse_id')));
					
					if(isset(Input::json('inv_item_id')['inv_item_id'.$i])){
						$inv_log_dtl = new InventoryItemLog;
						$inv_log_dtl->Job_Id = Input::json('jobid');
						$inv_log_dtl->InventoryItem_Id = Input::json('inv_item_id')['inv_item_id'.$i];
						$inv_id = Input::json('inv_item_id')['inv_item_id'.$i];								
						$inventory_ids = $this->getProdIdByInvID($inv_id);
						$inv_log_dtl->Product_Id = $inventory_ids[0]->Product_Id;					
						$inv_log_dtl->Channel_Id = Input::json('channel_id');
						$inv_log_dtl->Warehouse_Id = Input::json('warehouse_id');
						$inv_log_dtl->Stock_Count =  $avilable_stock_counts;
						$inv_log_dtl->Re_Order_Level = "0";
						$inv_log_dtl->Notes = "";
						$inv_log_dtl->Reason = "";
						$inv_log_dtl->LastCycleCount = "0";
						$inv_log_dtl->LastAdjustmentCount = "0";	
						$inv_log_dtl->Active = 1;
						$inv_log_dtl->save();
					}
				}else if(Input::json('old_shipment_qty')['old_shipment_qty'.$i] < Input::json('shipment_qty')['shipment_qty'.$i]){
					$new_balance_qty = Input::json('shipment_qty')['shipment_qty'.$i] - Input::json('old_shipment_qty')['old_shipment_qty'.$i];
					$new_balance_qty = Input::json('balance_qty')['balance_qty'.$i] - $new_balance_qty;
					$update_shipment_item_details = DB::select(DB::raw('update ShipmentItems set `Shipment_Qty` = '. "'" . Input::json('shipment_qty')['shipment_qty'.$i]."'" .',`Ship_Balance_Qty` ='. Input::json('shipment_qty')['shipment_qty'.$i].' where Shipment_Item_Id = ' . Input::json('shipment_item_id')['shipment_item_id'.$i]));  
					$order_balance_qty =Input::json('old_shipment_qty')['old_shipment_qty'.$i] - Input::json('shipment_qty')['shipment_qty'.$i];
					$new_order_balance_qty = Input::json('old_balance_qty')['old_balance_qty'.$i] + $order_balance_qty;
					$avilable_stock_counts = Input::json('avilable_stock_count')['avilable_stock_count'.$i] + $order_balance_qty;
					$update_inv_stock_count = DB::select(DB::raw('update inventoryitems set `Stock_Count` ='. $avilable_stock_counts .' where id = ' . Input::json('inv_item_id')['inv_item_id'.$i].' AND Channel_Id ='. Input::json('channel_name').' AND Warehouse_Id ='. Input::json('warehouse_id')));

					if(isset(Input::json('inv_item_id')['inv_item_id'.$i])){
						$inv_log_dtl = new InventoryItemLog;
						$inv_log_dtl->Job_Id = Input::json('jobid');
						$inv_log_dtl->InventoryItem_Id = Input::json('inv_item_id')['inv_item_id'.$i];
						$inv_id = Input::json('inv_item_id')['inv_item_id'.$i];	
						$inventory_ids =  $this->getProdIdByInvID($inv_id);
						$inv_log_dtl->Product_Id = $inventory_ids[0]->Product_Id;
						$inv_log_dtl->Channel_Id = Input::json('channel_id');
						$inv_log_dtl->Warehouse_Id = Input::json('warehouse_id');
						$inv_log_dtl->Stock_Count =  $avilable_stock_counts;
						$inv_log_dtl->Re_Order_Level = "0";
						$inv_log_dtl->Notes = "";
						$inv_log_dtl->Reason = "";
						$inv_log_dtl->LastCycleCount = "0";
						$inv_log_dtl->LastAdjustmentCount = "0";	
						$inv_log_dtl->Active = 1;
						$inv_log_dtl->save();
					}
				}				
			}			
		}
	}
	
	public function getChannelName($id){
		// Query To Fetch Products Details
		$channel_id = $id; 
		$channel__name_dat = DB::select(DB::raw("SELECT channel_name as Channel_Name FROM channels WHERE id =".$channel_id));
		$ch_name =  $channel__name_dat[0]->Channel_Name;
		return $ch_name;
	}
	
	public function getWarehouseName($id){
		// Query To Fetch warehouse Details
		$Warehouse_Id = $id; 
		$warehouse__name_dat = DB::select(DB::raw("SELECT warehouse_name as Warehouse_Name FROM Warehouses WHERE id =".$Warehouse_Id));
		$warehouse_name =  $warehouse__name_dat[0]->Warehouse_Name	;
		return $warehouse_name;
	} 
	
	public function getShipmentBoxNameFlag($shipmentid,$boxname){	  
	   $shipmentboxname_list = DB::select(DB::raw("SELECT count(*) AS cntrow FROM ShipmentsBoxes 
		WHERE BoxName = '".$boxname."' AND Active='1' AND Shipment_Id=".$shipmentid ));
		return  $shipmentboxname_list;
    }		
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getChannelProductDetails(){
	$channel_wise_warehouse_product = DB::select(DB::raw("SELECT INVENT.Product_Id, PRO.Product_Name,INVENT.Channel_Id,CHA.Channel_Name,WARE.Warehouse_Name
        from inventoryitems as INVENT
		INNER JOIN PRODUCTS AS PRO ON PRO.id=INVENT.Product_Id
		INNER JOIN channels AS CHA ON CHA.id=INVENT.Channel_Id
		INNER JOIN warehouses AS WARE ON WARE.id = INVENT.Warehouse_Id
		WHERE PRO.Active=".'"1"'." AND INVENT.Active=".'"1"'." AND INVENT.Channel_Id=". Input::json('channel_id')." AND INVENT.Warehouse_Id=". Input::json('warehouse_id')." group by PRO.id " ));
	return Response::json(array('response' => $channel_wise_warehouse_product));	
	}

	// Fetch Details From Inventory Table
	public function getWarehouseFromInventory(){
	$channel_wise_warehous = DB::select(DB::raw("SELECT inv.Warehouse_Id,inv.Stock_Count,ware.Warehouse_Name FROM InventoryItems AS inv 
		LEFT JOIN Warehouses AS ware ON ware.Warehouse_Id=inv.Warehouse_Id 
		WHERE inv.Channel_Id=".Input::json('channel_id')." Group By ware.Warehouse_Id")); 
	return Response::json(array('response' => $channel_wise_warehous)); 	
	}	
		
	public function showPackingSlipDetails(){
		$packing_Details = DB::select(DB::raw("SELECT Channel_Name, Date_Of_Shipment,ShipAddress1,RecipientName,ShipCity,ShipState,ShipZip 
									FROM Shipments WHERE id=".Input::json('id')));
			
		$shipmentbox_box_list = DB::select(DB::raw("SELECT sbox.id AS Shipment_Box_Id FROM ShipmentsBoxItems AS sitem
                                     LEFT JOIN shipmentsboxes AS sbox ON sbox.id = sitem.Shipment_Box_Id
									WHERE sbox.Shipment_Id=".Input::json('id')." AND sbox.Active=".'"1"'." group by sbox.id"));
									
		$shipmentbox_item_list = DB::select(DB::raw(" SELECT sbox.id AS Shipment_Box_Id,sitem.Boxed_Qty,sbox.BoxName,
                                            si.Ship_Balance_Qty,si.Shipment_Qty,sitem.SKU,sitem.Shipment_Box_Id,
                                            sitem.Item_Name,sbox.Shipment_Id,ship.TrackingNumber,inv.Product_Id,
                                            pro.Product_Description FROM shipmentsboxitems AS sitem
                                            LEFT JOIN shipmentsboxes AS sbox ON sbox.id = sitem.Shipment_Box_Id
                                            LEFT JOIN shipmentitems AS si ON si.id = sitem.Shipment_Item_Id
                                            LEFT JOIN inventoryitems AS inv ON inv.id = si.Inventory_Item_Id
                                            LEFT JOIN products AS pro ON pro.id = inv.Product_Id
                                            LEFT JOIN shipments AS ship ON ship.id = si.Shipment_Id
                                             WHERE sbox.Active=".'"1"'." AND sbox.Shipment_Id=".Input::json('id')." ORDER BY sitem.id"));
		
		$order_packing_Details = DB::select(DB::raw("SELECT ore.id AS Order_Id,ore.Order_Date,jstatus.Status_Name,
ore.id AS Order_Id,ship.id Shipment_Id,ore.BillAddress1,ore.BillName,ore.BillCompany,
ore.BillCity,ore.BillZip,ore.BillState,ore.BillPhone,
job.Job_Status_Id,ship.Date_Of_Shipment,ore.ShipName,
ore.ShipCompany,ship.Channel_Name,ship.RecipientName,
ship.ShipAddress1,ship.ShipAddress2,ship.ShipCity,ship.ShipState,ship.ShipZip,
ship.ShipCountry,ship.TrackingNumber FROM `shipments` AS ship
LEFT JOIN jobs AS job ON job.id=ship.Job_Id
LEFT JOIN orders AS ore ON ore.Job_Id = job.Parent_Job_Id
INNER JOIN job_type_status AS jstatus ON jstatus.id=job.Job_Status_Id
									WHERE ship.id=".Input::json('id')));
		
		return Response::json(array('packingResponse'=>$packing_Details,'boxlist' =>$shipmentbox_item_list,
									'boxname' =>$shipmentbox_box_list,'order_packing_Response'=>$order_packing_Details));
	}
			
	public function getInventoryProduct(){
		$inv_result=DB::select(DB::raw("SELECT OI.Order_Item,inv.Product_Id,OI.ItemName from InventoryItems as inv 
									LEFT JOIN OrderItems AS OI ON OI.Order_Item=inv.Product_Id 
									WHERE inv.Channel_Id=". Input::json('channel_id')." AND inv.Warehouse_Id=".Input::json('warehouse_id')));
		return Response::json(array('invResponse'=>$inv_result));
	}

	public function allowChannelRight($channel_right_name)
	{
		$requested_ch_right = $channel_right_name;
		$usergroup = User::find(Auth::id())->usergroup()->first();

		//select form usergroup channel rights where the channel_id and usergroup_id and ch_right
		$Right_list = DB::select(DB::raw(
			"SELECT ugchr.* FROM user_group_channel_right ugchr, channel_right chr
				WHERE  ugchr.userGroupID = ".$usergroup->id."
					and ugchr.channel_right_id =  chr.id
					and chr.channel_right_name = '".$requested_ch_right."'"));

		return Response::json(count($Right_list) >0);
	}

	public function allowDirectShipmentAdd()
	{
		return $this->allowChannelRight("AllowDirectShipmentAdd");
	}


	public function allowJTS($shipment_id,$requested_jt_right)
	{

		$usergroup = User::find(Auth::id())->usergroup()->first();
		$job_id =Shipments::find($shipment_id)->Job_Id;
		$job = Jobs::find($job_id);
		$channel_id = $job->Channel_Id;
		$job_status_id = $job->Job_Status_Id;
        $sql_select = "SELECT ugjtsr.* FROM user_group_status_right ugjtsr, job_type_right jtr
				WHERE ugjtsr.channel_id = ".$channel_id." and ugjtsr.userGroupID = ".$usergroup->id."
					and ugjtsr.job_type_status_id = ".$job_status_id."
					and ugjtsr.job_type_right_id =  jtr.id
					and jtr.job_type_right_name = '".$requested_jt_right."'";
		//select form usergroup channel rights where the channel_id and usergroup_id and ch_right
		$Right_list = DB::select(DB::raw($sql_select));

		return Response::json(count($Right_list) >0);
	}

	public function allowShipmentEditDelete()
	{
		$Shipment_id = Input::json('id');
		$requested_jt_right ="AllowShipmentEditDelete";
		return $this->allowJTS($Shipment_id,$requested_jt_right);
	}

	public function allowShipBoxAdd()
	{
		$Shipment_id = Input::json('id');
		$requested_jt_right ="AllowShipBoxAdd";
		return $this->allowJTS($Shipment_id,$requested_jt_right);
	}

	public function allowShipmentStatusChange()
	{
		$Shipment_id = Input::json('id');
		$requested_jt_right ="AllowShipmentStatusChange";
		return $this->allowJTS($Shipment_id,$requested_jt_right);
	}

	public function allowedShipmentStatuses()
	{
		$shipment_id = Input::json('id');
		$usergroup = User::find(Auth::id())->usergroup()->first();
		$job_id =Shipments::find($shipment_id)->Job_Id;
		$job = Jobs::find($job_id);
		$channel_id = $job->Channel_Id;
		$job_status_id = $job->Job_Status_Id;

		//select form usergroup channel rights where the channel_id and usergroup_id and ch_right
		$status_list = DB::select(DB::raw(
			"SELECT jts.* FROM job_type_status jts
				WHERE  jts.id =   ".$job_status_id."
					or   jts.id in
						(Select jtst.to_status_id
							From  job_type_status_flow jtst
							where jtst.from_status_id= '".$job_status_id."')"));


		return Response::json(array('response' => $status_list));
	}


    public  function channel($id){
        $requested_ch_right ="AllowShipmentView";
        $usergroup = User::find(Auth::id())->usergroup()->first();
        $started_shipment_list = DB::select(DB::raw("SELECT ore.id as Order_Id,ore.Order_Date,jstatus.Status_Name,
			CAR.Carrier_Name,CARSN.CarrierService_Name,ore.id as Order_Id,ship.id as Shipment_Id, job.Job_Type_Id, job.Channel_id ,
			job.Job_Status_Id, ship.Date_Of_Shipment,ship.ShipmentCost,ship.Carrier_Id,
			IF(ship.SignatureRequired='1','Required', 'Not Required') AS SignatureRequired,
			ship.CarrierService_Id,ship.Channel_Name,ship.Warehouse_Name,ship.RecipientName,
			IF(ship.EmailNotification='1','Notify', 'Do Not Notify') AS EmailNotification,
			ship.ShipAddress1,ship.ShipAddress2,ship.ShipCity,ship.ShipState,ship.ShipZip,
			ship.ShipCountry,ship.ShipEmail,ship.ShippingAccount,ship.TrackingNumber,ship.ShipmentCost,
			ship.ActualShipmentCost,ship.AdditionalNotes FROM `shipments` AS ship
			LEFT JOIN Jobs AS job ON job.id=ship.Job_Id
			LEFT JOIN Orders AS ore ON ore.Job_Id = job.Parent_Job_Id
			LEFT JOIN Carrier  AS CAR ON CAR.id = ship.Carrier_Id
			LEFT JOIN CarrierServices AS CARSN ON CARSN.id = ship.CarrierService_Id
			INNER JOIN job_type_status AS jstatus ON jstatus.id=job.Job_Status_Id
			WHERE job.Channel_id = ".$id." group by ship.id
			 HAVING job.Channel_id in(select ugchr.channel_id
										from user_group_channel_right ugchr, channel_right chr
										  where ugchr.userGroupID = ".$usergroup->id."
				 								and chr.channel_right_name = '".$requested_ch_right."')"));
        return Response::json(array('response' => $started_shipment_list));
    }
    public function status($id){
        $requested_ch_right ="AllowShipmentView";
        $usergroup = User::find(Auth::id())->usergroup()->first();
        $started_shipment_list = DB::select(DB::raw("SELECT ore.id as Order_Id,ore.Order_Date,jstatus.Status_Name,
			CAR.Carrier_Name,CARSN.CarrierService_Name,ore.id as Order_Id,ship.id as Shipment_Id, job.Job_Type_Id, job.Channel_id ,
			job.Job_Status_Id, ship.Date_Of_Shipment,ship.ShipmentCost,ship.Carrier_Id,
			IF(ship.SignatureRequired='1','Required', 'Not Required') AS SignatureRequired,
			ship.CarrierService_Id,ship.Channel_Name,ship.Warehouse_Name,ship.RecipientName,
			IF(ship.EmailNotification='1','Notify', 'Do Not Notify') AS EmailNotification,
			ship.ShipAddress1,ship.ShipAddress2,ship.ShipCity,ship.ShipState,ship.ShipZip,
			ship.ShipCountry,ship.ShipEmail,ship.ShippingAccount,ship.TrackingNumber,ship.ShipmentCost,
			ship.ActualShipmentCost,ship.AdditionalNotes FROM `shipments` AS ship
			LEFT JOIN Jobs AS job ON job.id=ship.Job_Id
			LEFT JOIN Orders AS ore ON ore.Job_Id = job.Parent_Job_Id
			LEFT JOIN Carrier  AS CAR ON CAR.id = ship.Carrier_Id
			LEFT JOIN CarrierServices AS CARSN ON CARSN.id = ship.CarrierService_Id
			INNER JOIN job_type_status AS jstatus ON jstatus.id=job.Job_Status_Id
			WHERE job.Job_Status_Id = ".$id." group by ship.id
			 HAVING job.Channel_id in(select ugchr.channel_id
										from user_group_channel_right ugchr, channel_right chr
										  where ugchr.userGroupID = ".$usergroup->id."
				 								and chr.channel_right_name = '".$requested_ch_right."')"));
        return Response::json(array('response' => $started_shipment_list));
    }

}
