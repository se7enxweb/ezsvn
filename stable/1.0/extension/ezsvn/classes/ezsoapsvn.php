<?php

class eZSOAPsvn
{
	function eZSOAPsvn ()
	{
	}
	function config ( $remote_id = false )
	{
		include_once( 'kernel/classes/ezcontentobject.php' );
		
		if ( !$remote_id )
			return new eZSOAPFault( 100, 'NO ID' ) ;
		
		if ( is_numeric( $remote_id ) )
		{
			$co = eZContentObject::fetch( $remote_id );
		}
		else
		{
			$co = eZContentObject::fetchByRemoteID( $remote_id );
		}
		
		if ( !get_class( $co ) == 'ezcontentobject' )
			return new eZSOAPFault( 101, 'No Object found for id/remoteid '.$remote_id ) ;
		
		$datamap =& $co->attribute('data_map');

		if ( !array_key_exists( 'base', $datamap ) or !is_object( $datamap['base'] ) )
			return new eZSOAPFault( 102, 'No Objectattribute base found for id/remoteid '.$remote_id ) ;

		$base = $datamap['base']->content();
		$revision = trim( $datamap['revision']->content() );
		#get base
		$ini =& eZINI::instance('svn.ini');
		
		$result =array();
		if ( $ini->hasVariable( 'Repositories', $base ) )
		{	
			if ( $revision )
				$result[] = array_merge( $ini->variable( 'Repositories', $base ), array ( "name" => $base, 'revision' => $revision ) );	
			else
				$result[] = array_merge( $ini->variable( 'Repositories', $base ), array ( "name" => $base ) );

		}
		else
		{
			$result[] = array( );
		}
		$relation_list = $datamap['svnitems']->content();

		foreach ( $relation_list['relation_list'] as $item )
		{
			
			$itemco =& eZContentObject::fetch( $item['contentobject_id'] );
			$itemdatamap =& $itemco->attribute('data_map');
			$addition = array();
			if ( $itemdatamap['placement']->content() )
				$addition['placement'] = $itemdatamap['placement']->content();
			if ( $itemdatamap['user']->content() )
				$addition['user'] = $itemdatamap['user']->content();
			if ( $itemdatamap['password']->content() )
				$addition['password'] = $itemdatamap['password']->content();
			if ( $itemdatamap['revision']->content() )
				$addition['revision'] = $itemdatamap['revision']->content();
			if ( $itemdatamap['url']->content() )
				$addition['url'] = $itemdatamap['url']->content();
		
				
			$ezenum =& $itemdatamap['type']->content();
			$list =& $ezenum->attribute('enumobject_list');
			
			if ( $list[0]->EnumValue )
			{
				$addition['type'] = $list[0]->EnumValue;
			}
			else
			{
				$addition['type'] = 'export';
			}
			$result[] = $addition;
		}
		
		return $result;
	}
}
?>
