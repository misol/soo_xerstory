<?php
class soo_xerstory extends WidgetHandler
{
	function proc($widget_info)
	{
		// load document_srl
		$document_srl = Context::get('document_srl');
		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($document_srl);
		if(!$oDocument->get('member_srl')) return;

		if(!$widget_info->popular_count) $widget_info->popular_count = 2;
		if(!$widget_info->list_count) $widget_info->list_count = 7;
		$widget_info->list_count++;

		//캐시 불러오기
		$oCacheHandler = CacheHandler::getInstance('object');
		if($oCacheHandler->isSupport())
		{
			$cache_key = 'widget_soo_xerstory:' . $oDocument->get('member_srl');
			$document_list = $oCacheHandler->get($cache_key);
		}
		
		if(!$document_list)
		{
			$no_document_srl = array(1);
			$popular_list = array();
			$newest_list = array();
			
			//인기글
			if($widget_info->list_type != 'newest' && ($widget_info->popular_vote || $widget_info->popular_readed))
			{
				$args = new stdClass;
				if($widget_info->popular_base == 'and')
				{
					$args->readed_count = $widget_info->popular_readed;
					$args->voted_count = $widget_info->popular_vote;
				}
				else
				{
					$args->readed_count_or = $widget_info->popular_readed;
					$args->voted_count_or = $widget_info->popular_vote;
				}
				
				if($widget_info->popular_time)
				{
					$args->regdate = date("YmdHis",strtotime(sprintf('-%s day', $widget_info->popular_time)));
				}
				
				if($widget_info->list_type == 'popular')
				{
					$args->list_count = $widget_info->list_count;
				}
				else
				{
					$args->list_count = $widget_info->popular_count;
				}
				
				if($widget_info->each_module == 'Y')
				{
					$args->module_srl = $oDocument->get('module_srl');
				}
				else if($widget_info->except_module)
				{
					$args->no_module_srl = explode(',', $widget_info->except_module);
				}
				
				$args->statusList = array('PUBLIC');
				$args->sort_index = $widget_info->popular_sort;
				$args->member_srl = $oDocument->get('member_srl');
				$args->no_document_srl = $no_document_srl;
				$tmp_output = executeQueryArray('widgets.soo_xerstory.getDocumentList', $args);
				
				if($tmp_output->data)
				{
					foreach($tmp_output->data as $key => $val)
					{
						$no_document_srl[] = $val->document_srl;
						
						$oDocument = new documentItem();
						$oDocument->setAttribute($val, false);
						$oDocument->popular = 'Y';
						$popular_list[] = $oDocument;
					}
				}
			}
			
			//최신글
			if($widget_info->list_type != 'popular' && $widget_info->list_count > count($popular_list))
			{
				$args = new stdClass;
				
				if($widget_info->each_module == 'Y')
				{
					$args->module_srl = $oDocument->get('module_srl');
				}
				else if($widget_info->except_module)
				{
					$args->no_module_srl = explode(',', $widget_info->except_module);
				}
				
				$args->statusList = array('PUBLIC');
				$args->member_srl = $oDocument->get('member_srl');
				$args->no_document_srl = $no_document_srl;
				$args->list_count = $widget_info->list_count - count($popular_list);
				$tmp_output = executeQueryArray('widgets.soo_xerstory.getDocumentList', $args);
				
				if($tmp_output->data)
				{
					foreach($tmp_output->data as $key => $val)
					{
						$oDocument = new documentItem();
						$oDocument->setAttribute($val, false);
						$newest_list[] = $oDocument;
					}
				}
			}
			
			if(!empty($popular_list) || !empty($newest_list))
			{
				$merge_list = array_merge($popular_list, $newest_list);
			}
			
			$document_list = array();
			if($merge_list)
			{
				foreach($merge_list as $key => $val)
				{
					$document_list[$val->document_srl] = $val;
				}
			}
			
			//캐시 저장
			if($oCacheHandler->isSupport())
			{
				// 위젯은 글 저장을 캐치할 수 없음. 시간에 따른 캐시.
				if(!$widget_info->widget_cache)
				{
					$widget_info->widget_cache = '0m';
				}

				$widget_cache = $widget_info->widget_cache;
				if (preg_match('/^([0-9\.]+)([smhd])$/i', $widget_cache, $matches))
				{
					// For Rhymix
					$multipliers = array('s' => 1, 'm' => 60, 'h' => 3600, 'd' => 86400);
					$widget_cache = intval(floatval($matches[1]) * $multipliers[strtolower($matches[2])]);
				}
				else
				{
					// For XE
					$widget_cache = intval(floatval($widget_cache) * 60);
				}

				$oCacheHandler->put($cache_key, $document_list, $widget_cache);
			}
		}
		
		unset($document_list[$document_srl]);
		if(empty($document_list)) return;

		Context::set('widget_info', $widget_info);
		Context::set('document_list', $document_list);

		$oTemplate = new TemplateHandler;
		$tpl_path = sprintf('%sskins/%s', $this->widget_path, $widget_info->skin);
		return $oTemplate->compile($tpl_path, "show_author_document");
	}
}
/* End of file soo_xerstory.class.php */
/* Location: ./widgets/soo_xerstory/soo_xerstory.class.php */