listen {
  port    = 4040
  address = "0.0.0.0"
}

namespace "nginx" {
  source = {
    files = [
      "/var/log/nginx/access.log"
    ]
  }

  format = "$remote_addr - $remote_user [$time_local] \"$request_method $request_uri $server_protocol\" $status $request_time $upstream_response_time $body_bytes_sent \"$http_referer\" \"$http_user_agent\" \"$http_x_forwarded_for\" $server_name $request_length $upstream_addr"

  labels {
    app         = "nginx"
    environment = "production"
  }

  relabel "request_uri" { from = "request_uri" }
  relabel "server_name" { from = "server_name" }
  relabel "upstream_addr" { from = "upstream_addr" }

  # ПРАВИЛЬНЫЙ СИНТАКСИС ДЛЯ МАССИВОВ В HCL
  histogram_buckets = [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0]
}