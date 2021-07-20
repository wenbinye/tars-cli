# Command Line Utilities for TARS

| Command     | Description                       |
|-------------|-----------------------------------|
| configure   | Configures API parameters         |
| server:list | Lists server by app or id         |
| deploy      | Deploy server                     |
| patch       | Uploads patch file or apply patch |
| patch:list  | Lists server patches              |

## Apply configuration

```json
{
    "app.server": {
        "server_type": "tars_php",
        "template_name": "winwin.php",
        "nodes": [
            "192.168.0.209"
        ],
        "adapters": [
            {
                "obj_name": "ClientObj",
                "port": 10202,
                "port_type": "tcp",
                "protocol": "tars",
                "thread_num": 1,
                "max_connections": 100000,
                "queuecap": 50000,
                "queuetimeout": 20000
            }
        ]
    }
}
```
