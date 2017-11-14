# workerman
Workerman 用法记录

## 项目说明

### Workerman 的一般使用方式

在入口文件（本项目中的 `index.php`）中，创建不同的 Worker 对象进行统一管理。这里创建了一个 tcp 的 server，同样还可以创建一个 http、websocket 的 server 等等。

### 通过配置文件引入文件

在入口文件中，可以配合配置文件，通过对配置文件中 Worker 的配置，进行文件引入或者 Worker 对象的实例化。
这里在 `client.php` 中引入了两个 tcp 的 client。

### 其他示例

在 `example` 目录下，根据不同的应用，创建了不同的示例。

## 其他

在使用 Workerman 的时候，尽量将 `onError` `onClose` 等监听事件的处理写完整，这样，会在出错的时候，及时应对。

比如在做 tcp 的时候，如果在 server 未启动的情况下，启动了 client，那么将会一直报错。为了规避不可预测的结果，需要将事件处理写完成。

在被 `onError` 事件监听到的时候，可以使用 Worker::stop() 来停止 Worker，之后可以在 `onClose` 监听事件中，进行例如 `reConnect` 重连等相关处理。

