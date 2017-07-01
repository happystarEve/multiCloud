<?php
class Controller_Treenode{
	static public function treejson(){
		if(!empty($_GET['act']) && $_GET['act']=="tree"){
			//获取全部文件夹
			$rs_allfolder = Db::select('folderId','parentId','folderName')
						->from('foldertree')
						->fetchAll();
			$i = 0;
			foreach ($rs_allfolder as $row_allfolder) {
				$treeNodes[$i] = array(
					"id" => $row_allfolder['folderId'],
					"pId" => $row_allfolder['parentId'],
					"name" => $row_allfolder['folderName'],
					"isParent" => true
				);
				$i++;
			}
			$treeNodes = json_encode($treeNodes);
			$result = '{"success":true,"msg":'.$treeNodes.'}';
			echo $result;
		}
	}

	static public function get_path($folderId){
	    // 查找当前节点的父节点的ID，这里使用表自身与自身连接实现
	    $sql = "
	        SELECT c1.parentId, c2.folderName AS parent_name 
	        FROM foldertree AS c1
	 
	        LEFT JOIN foldertree AS c2 
	        ON c1.parentId=c2.folderId 
	 
	        WHERE c1.folderId='$folderId' ";
	    $result = mysqli_query(Db::$_conn,$sql);
	    $row = mysqli_fetch_array($result,MYSQLI_BOTH);//现在$row数组存了父亲节点的ID和名称信息
	 
	    // 将树状路径保存在数组里面
	    $path = array();
	 
	    //如果父亲节点不为空（根节点），就把父节点加到路径里面
	    if ($row['parentId']!=NULL) 
	    {
	        //将父节点信息存入一个数组元素
	        $parent[0]['folderId'] = $row['parentId'];
	        $parent[0]['folderName'] = $row['parent_name'];
	 
	        //递归的将父节点加到路径中
	        $path = array_merge(Controller_Treenode::get_path($row['parentId']), $parent);
	    }
	 
	   return $path;
	}

	static public function insert_children($folderId, $parentId) {
	    // 获得当前节点的所有孩子节点（直接孩子，没有孙子）
	    $result = mysqli_query(Db::$_conn,"SELECT * FROM foldertree WHERE parentId='$folderId'");
	 
	    // 遍历孩子节点，打印节点
	    while ($row = mysqli_fetch_array($result,MYSQLI_BOTH)) 
	    {
	        $newInsert = Db::insert('foldertree')->rows(array(
				'folderName' => $row['folderName'],
				'parentId' => $parentId,
			))->query();
	
	        $newparentId = Db::select('folderId')
	        			->from('foldertree')
	        			->where('folderName=?',$row['folderName'])
	        			->where('parentId=?',$parentId)
	        			->limit(1)
	        			->fetch();
	       // 递归所有的孩子节点
	       Controller_Treenode::insert_children($row['folderId'], $newparentId['folderId']);
	    }
	}

	static public function delete_children($folderId) {
	    // 获得当前节点的所有孩子节点（直接孩子，没有孙子）
	    $result = mysqli_query(Db::$_conn, "SELECT * FROM foldertree WHERE parentId='$folderId'");
	 
	    // 遍历孩子节点，打印节点
	    while ($row = mysqli_fetch_array($result,MYSQLI_BOTH)) 
	    {
	       // 递归所有的孩子节点
	       Controller_Treenode::delete_children($row['folderId']); 
	       $rs_delete = Db::delete('foldertree')->where('parentId=?',$row['folderId'])->query();
	    }
	}

	static public function display_children($folderId){
	    // 获得当前节点的所有孩子节点（直接孩子，没有孙子）
	    $result = mysqli_query(Db::$_conn, "SELECT * FROM foldertree WHERE parentId='$folderId'");
	 	$childrenArray = array();
	 	
	    // 遍历孩子节点，打印节点
	    while ($row = mysqli_fetch_array($result,MYSQLI_BOTH)) 
	    {
	        $children[0]['folderId'] = $row['folderId'];
	        $children[0]['folderName'] = $row['folderName'];
	        // 递归的打印所有的孩子节点
	 		$childrenArray = array_merge($childrenArray, Controller_Treenode::display_children($row['folderId']),$children);
	 	
	    }
	    return $childrenArray;
	}
}
?>
