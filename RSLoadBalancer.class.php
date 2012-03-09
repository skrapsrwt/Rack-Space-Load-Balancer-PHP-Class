<?php
/**
 * Unofficial Rackspace Load Balancer PHP Class
 * New BSD License (http://en.wikipedia.org/wiki/BSD_licenses#3-clause_license_.28.22New_BSD_License.22_or_.22Modified_BSD_License.22.29)
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in the
 *     documentation and/or other materials provided with the distribution.
 *   * Neither the name of the <organization> nor the
 *     names of its contributors may be used to endorse or promote products
 *     derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL ANYONE BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
*/

class RSAuthenticate(){
	var $auth_url = "/auth";
	var $api_url = "";
	var $api_key = "";
	var $json_query = "";
	var $username = "";

	function execute(){

	}

	function setUsername($username){
		$this->username = $username;
	}
	
	function setApiKey($key){
		$this->api_key = $key;
	}

	function auth_json(){
		$this->json_query='{"credentials":{"username":"'.$this->username.'","key":"'.$this->api_key.'"}}';
	}

	function setApiURL($url){
		switch($url){
			case "uk":
				$this->api_url = "https://lon.identity.api.rackspacecloud.com/v1.1/";
				break;
			case "us":
				$this->api_url = "https://identity.api.rackspacecloud.com/v1.1/";
				break;
		}
	}

}

class RSLoadBalancer::RSAuthenticate(){

	
	var $loadbalancer_url = "/loadbalancers"; 
	var $algorithim = "WEIGHTED_LEAST_CONNECTIONS"; 
/**             Balancing algorithim
*		LEAST_CONNECTIONS 		The node with the lowest number of connections will receive requests.
*		
*		RANDOM 				Back-end servers are selected at random.
*
*		ROUND_ROBIN 			Connections are routed to each of the back-end servers in turn.
*
*		WEIGHTED_LEAST_CONNECTIONS 	Each request will be assigned to a node based on the number of concurrent 
*						connections to the node and its weight.
*
*		WEIGHTED_ROUND_ROBIN 		A round robin algorithm, but with different proportions of traffic being 
*						directed to the back-end nodes. Weights must be defined as part of the 
*						load balancer's node configuration. 
**/
	var $connection_logging = "false"; // bool true or false
	var $nodes = ""; // Array of nodes format $nodes['nodename'] = "ipaddress";
	var $protocol = ""; //set protocol
	var $proto_port = "";
	var $available_protocols = Array("HTTP" => "80", "HTTPS" => "443", "POP3" => "110", "SMTP" => "25", "FTP" => "21", "IMAPv4" => "143", "POP3S" => "995", "IMAPS" => "993", "SSMTP" => "435");
	var $ext_ip_sddr = "";
	var $cluster_name = "";
	var $loadbalancer_name = "";
	var $node_weights = ""; // $node_weights['node_name'] = weight
	var $rate = "";
	var $node_condition = ""; //$node_condition['node_name'] = condition;
	var $node = "";
	var $json_query = "";

	// Connection throttling variables
	var $max_connections = "";
	var $min_connections = "";
	var $max_connection_rate = "";
	var $rate_interval = ""; 

	function listPersistence($lb_id){
		if(!$lb_id)
			return 1;
		$this->setPersistence($lb_id);
	}

	function enablePersistence($lb_id){
		if(!$lb_id)
			return 1;
		$this->setPersistence($lb_id, true);
	}

	function disablePersistence(){
		if(!$lb_id)
			return 1;
		$this->setPersistence($lb_id, false);
	}

	function setPersistence($lb_id, $mode){
//	GET 	/loadbalancers/loadBalancerId/sessionpersistence 	List session persistence configuration.
//	PUT 	/loadbalancers/loadBalancerId/sessionpersistence 	Enable session persistence.
//	DELETE 	/loadbalancers/loadBalancerId/sessionpersistence 	Disable session persistence.

		if(!$lb_id)
			return 1;
		switch($mode){
			case "true":
				$method = "PUT";
				break;
			case "false":
				$method = "DELETE";
				break;
			default:
				$method = "GET";
		}
		if($method == "PUT")
			$this-json_query='"{"sessionPersistence":{"persistenceType":"HTTP_COOKIE"}}';

		$url = $this->loadbalncer_url.'/'.$lb_id.'/sessionpersistence';
		return $this->execute($url, $method);
	
	}
	function listLogging($lb_id){
		//GET 	/loadbalancers/loadBalancerId/connectionlogging 	View current configuration of connection logging.
		if(!$lb_id)
			return 1;
		$url = $this->loadbalncer_url.'/'.$lb_id.'/connectionlogging';
		return $this->execute($url, "GET");
	}

	function enableLogging($lb_id){
		if(!$lb_id)
			return 1;
		return $this->setLogging($lb_id,true);
	}

	function disableLogging($lb_id){
		if(!$lb_id)
			return 1;
		return $this->setLogging($lb_id,false);
	}

	function setLogging($lb_id,$mode){
		//PUT 	/loadbalancers/loadBalancerId/connectionlogging 	Enable or disable connection logging.
		$url = $this->loadbalancer_url.'/'.$lb_id.'/connectionlogging';		
		$this->json_query='{"connectionLogging":{"enabled":'.$mode.'}}';
		return $this->execute($url,"PUT");
	}		
	function listThrottle($lb_id){
		//GET 	/loadbalancers/loadBalancerId/connectionthrottle 	List connection throttling configuration.
		if(!$lb_id)
			return 1;	
		$url = $this->loadbalancer_url.'/'.$lb_id.'/connectionthrottle';
		return $this->execute($url, "GET");
	}
	
	function setThrottle($max, $min, $max_rate, $rate_int){
		if(!$max || !$min || !$max_rate || !$rate_int)
			return 1;

		$this->max_connections = $max;
		$this->min_connections = $min;
		$this->max_connection_rate = $max_rate;
		$this->rate_interval = $rate_int;
	}
 
	function updateThrottle($lb_id){
		//PUT 	/loadbalancers/loadBalancerId/connectionthrottle 	Update throttling configuration.
		if(!$lb_id)
			return 1;	

		$this->json_query='{"connectionThrottle":{ "maxConnections":'.$this->max_connections.',';
		$this->json_query.='"minConnections":'.$this->min_connections.',"maxConnectionRate": '.$this->max_connection_rate.',';
		$this->json_query.='"rateInterval": '.$this->rate_interval.'}}';

		$url = $this->loadbalancer_url.'/'.$lb_id.'/connectionthrottle';
		return $this->execute($url, "PUT");
	}

	function deleteThrottle($lb_id){
		//DELETE 	/loadbalancers/loadBalancerId/connectionthrottle 	Remove connection throttling configurations.
		if(!$lb_id)
			return 1;
		$url = $this->loadbalancer_url.'/'.$lb_id.'/connectionthrottle';
		return $this->execute($url, "DELETE");
	}

	function execute(){
		$cparams = array(
			'http' => array(
				'method' => $verb,
				'ignore_errors' => true
			)
		);

		$params = http_build_query($params);
		$cparams['http']['content'] = $params;
	}

	function updateLoadBalancer(){

	}

	function getProtoPort(){
		foreach($this->available_protocols as $key => $port){
				if($key == $this->protocol){
					$this->setProtoPort($port);
					break;
				}
		}
	}

	function setProtoPort($port){
		$this->proto_port = $port;
	}
	
	function updateLoadBalancer_json(){
	
	}
	
	function removeLoadBalancer_json(){

	}
	
	function setRate($rate){

	}
	
	function listLoadBalancers(){
		return $this->listLoadBalancer();
	}

	function deleteLoadBalancer($id){
		//VERB DELETE
		$url = $this->loadbalancer_url.'/'.$id;
		return $this->execute($url,"DELETE");
	}
	function listLoadBalancerById($id){
		$url = $this->loadbalancer_url.'/'.$id;
		return $this->execute($url,"GET");
	}

	function listLoadBalancer($ip){
		//GET request to /loadbalancers ; single loadbalancer use ?nodeaddress=ipaddress
		if($ip)		
			$url = $this->loadbalancer_url.'?nodeaddress='.$ip;
		else
			$url = $this->loadbalancer_url;		
		return $this->execute($url,"GET");
	}

	function listNodes($lb_id, $node_id){
		if(!$lb_id)
			return 1;
		$url = $this->loadbalancer_url.'/'.$lb_id.'/nodes';
		if($node_id)
			$url .= '/'.$node_id;
		
		return $this->execute($url,"GET");
	}

	function addNode($lb_id){
		if(!$lb_id)
			return 1;
		$url = $this->loadbalancer_url.'/'.
		return $this->execute($url,"POST");
	}

	function getProtocols(){
		//GET 	/loadbalancers/protocols 	List all supported load balancing protocols.
		$url = $this->loadbalancer_url.'/protocols';
		return $this->execute($url,"GET");
	}
	
	function listVirtualIP($lb_id){
		//GET 	/loadbalancers/loadBalancerId/virtualips 	List all virtual IPs associated with a load balancer.
		if(!$lb_id)
			return 1;
		$url = $this->loadbalancer_url.'/'.$lb_id.'/virtualips';
		return $this->execute($url,"GET");
	}

	function addVirtualIP($lb_id){
		//POST 	/loadbalancers/loadBalancerId/virtualips 	Add virtual IP version 6
		if(!$lb_id)
			return 1;

		$this->json_query='{"type":"PUBLIC","ipVersion":"IPV6"}';
		$url = $this->loadbalancer_url.'/'.$lb_id.'virtualips';		
		return $this->execute($url, "POST");
	}

	function removeVirtualIP($lb_id,$vip_id){
		//DELETE 	/loadbalancers/loadBalancerId/virtualips/ id		
		if(!$lb_id || $vip_id)
			return 1;
				
		$url = $this->loadbalancer_url.'/'.$lb_id.'/virtualips/'.$vip_id;
		return $this->execute($url,"DELETE");
	}

	function updateNode($lb_id,$node_id){
		//loadbalancers/loadBalancerId/nodes/nodeid
		if(!$lb_id && !$node_id)
			return 1;
	
		$json_an_q.='{"nodes":[';
		$json_node.='{"address":"'.$this->node[$node_id].'","port":"'.$this->port.'",';
		$json_node.='"condition":"'.$this->node_condition.'",';			
		$json_node.='"weight":'.$this->node_weight.'},';
	
	}

	function deleteNode($lb_id,$node_id){
		// DELETE /loadbalancers/loadBalancerId/nodes/ nodeId 
		if(!$id || $node_id)
			return 1;
		$url = $this->loadbalancer_url.'/'.$lb_id.'/nodes/'.$node_id;	
		return $this->execute($url,"DELETE");
	}

	function addNode_json(){
		///loadbalancers/loadBalancerId/nodes		
		//{"nodes": [{"address": "10.1.1.1","port": 80,"condition": "ENABLED", "weight": }]}
			
		$json_an_q.='{"nodes":[';

		foreach($this->nodes as $nodename => $nodeip){
			$json_node.='{"address":"'.$nodeip.'","port":"'.$this->port.'",';
			$json_node.='"condition":"'.$this->node_condition[$nodename].'",';			
			$json_node.='"weight":'.$this->node_weight[$nodename].'},';
		}
	
		$json_node = substr($json_node, length($json_node), -1);
		$json_clb_q.=$json_node.']}';

		$this->json_query = $json_an_q;
	}
	
	function getLoadBalancerStats($id){
		//Sample response
		//{"connectTimeOut":10,"connectError":20,"connectFailure":30,"dataTimedOut":40,"keepAliveTimedOut":50,"maxConn":60}
		$url = $this->loadbalancer_url.'/'.$id.'/stats';
		return $this->execute($url, "GET");
	}
	
	function updateLoadBalancer(){
		$this->execute();
	}

	function updateLoadBalancer_json(){
		//PUT request to /loadbalancers     name, algorithm, protocol, port 
		$json_ulb_q ='{"loadBalancer":{"name": "'.$this->loadbalancer_name.'","algorithm": "'.$this->algorithim.'","protocol":';
		$json_ulb_q .='"'.$this->proto.'","port": '.$this->proto_port.',"connectionLogging": '.$this->connection_logging.'}}';
		$this->json_query = $json_ulb_q;	
	}
		
	function createLoadBalancer(){
		return $this->execute($url,"CREATE",$this->json_query);
	}	

	function createLoadBalancer_json(){        
		if(!$this->proto_port)
			$this->getProtoPort;

	$json_clb_q='"loadBalancer":{';
        $json_clb_q.='"name":"'.$this->loadbalancer_name.'","id":2200,"port":'.$this->proto_port.',"protocol":"'.$this->protocol.'",';
        $json_clb_q.='"algorithm":"'.$this->algorithim.'","status":"BUILD","cluster":{"name":"'.$this->cluster_name.'"},';        
	$json_clb_q.='"nodes":[';	
	$json_node="";
	
		foreach($this->nodes as $nodename => $nodeip){
			$json_node.='{"address":"'.$nodeip.'","id":"2208","port":"'.$this->port.'",';
			$json_node.='"status":"ONLINE","condition":"ENABLED","weight":'.$this->nodeweight[$nodename].'},';
		}
	
	$json_node = substr($json_node, length($json_node), -1);
	$json_clb_q.=$json_node.'],';
	//virtual ips are external ip addresses
	$json_clb_q.='"virtualIps":[';
	$json_extIP = "";

	foreach($extIPAddr as $ipname => $ipaddr){
		$json_extIP.='{"address":"'.$ipaddr.'","id":15,"type":"PUBLIC","ipVersion":"IPV4"},';
        	$json_extIP.='{"address":"fd24:f480:ce44:91bc:1af2:15ff:0000:0005","id":9000137,"type":"PUBLIC","ipVersion":"IPV6"}';
	}
	$json_extIP = substr($json_extIP, length($json_extIP), -1);
	
	$json_clb_q.='"],"';
        $json_clb_q.='"created":{"time":"'.date("Y-M-D").'TO'.date("H:M:S").'-'.date("H:S").'"},"';
	$json_clb_q.='"modified":{"time":"'.date("Y-M-D").'TO'.date("H:M:S").'-'.date("H:S").'"},"';
        $json_clb_q.='"connectionLogging":{"enabled":'.$this->connection_logging.'},';
	//sourceAddresses are internal addresses        
	$json_clb_q.='"sourceAddresses":{"ipv6Public":"2001:4801:79f1:1::1/64","ipv4Servicenet":"10.0.0.0","ipv4Public":"10.12.99.28"}}';

	$this->json_query = $json_clb_q;
	
	}


	function setNodeWeights($weights){
		$this->node_weights = $weights	
	}

	function setLoadBalancerName($name){
		$this->loadbalancer_name = $name;
	}

	function setClusterName($name){
		$this->clustername = $name;
	}
	function setExternalIPAddr($ips){
		$this->ext_ip_addr = $ips;
	}

	function setProtocol($proto){
		$this->protocol = $proto;
	}

	function setConnectionLogging($value){
		$this->connection_logging = $value;	
	}
	
}
?>
