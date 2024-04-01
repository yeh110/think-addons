<?php
/**
 * Desc: 生成插件
 * User: circle
 * Date: 2024/4/1
 * Email: <yeh110@qq.com>
 **/

namespace think\addons\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class AddonsBuild extends Command
{
    public function configure()
    {
        $this->setName('addons:build')
            ->addArgument('name', Argument::OPTIONAL, '插件名称，小写字母开头')
            ->addOption('title', 't', Option::VALUE_OPTIONAL, '插件标题')
            ->addOption('desc', 'd', Option::VALUE_OPTIONAL, '插件描述')
            ->addOption('author', 'a', Option::VALUE_OPTIONAL, '插件作者')
            ->addOption('version ', 've', Option::VALUE_OPTIONAL, '插件版本')
            ->setDescription('快速插件目录生成命令');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->basePath = $this->app->getRootPath() . 'addons' . DIRECTORY_SEPARATOR;
        $app            = $input->getArgument('name') ?: '';
        if (empty($app)) {
            $output->writeln("<info>创建失败，请输入插件名称</info>");
            return;
        }
        $option['title']       = $input->getOption('title') ?: $app;
        $option['description'] = $input->getOption('desc') ?: $app;
        $option['author']      = $input->getOption('author') ?: 'Circle';
        $option['version']     = $input->getOption('version') ?: '1.0.0';
        $list                  = [
            '__dir__' => ['controller', 'model'],
        ];
        $this->buildApp($app, $list, $option);
        $output->writeln("<info>[{$option['title']}]插件生成成功</info>");
    }


    /**
     * 创建应用
     * @access protected
     * @param string $app 应用名
     * @param array $list 目录结构
     * @param array $option 插件选项
     * @return void
     */
    protected function buildApp(string $app, array $list = [], array $option = []): void
    {
        if (!is_dir($this->basePath . $app)) {
            // 创建应用目录
            mkdir($this->basePath . $app);
        }
        $appPath   = $this->basePath . ($app ? $app . DIRECTORY_SEPARATOR : '');
        $namespace = 'addons' . ($app ? '\\' . $app : '');

        // 创建配置文件和公共文件

        foreach ($list as $path => $file) {
            if ('__dir__' == $path) {
                // 生成子目录
                foreach ($file as $dir) {
                    $this->checkDirBuild($appPath . $dir);
                }
            } elseif ('__file__' == $path) {
                // 生成（空白）文件
                foreach ($file as $name) {
                    if (!is_file($appPath . $name)) {
                        file_put_contents($appPath . $name, 'php' == pathinfo($name, PATHINFO_EXTENSION) ? '<?php' . PHP_EOL : '');
                    }
                }
            }
        }
        $this->buildCommon($app, $option);
        // 创建模块的默认页面
        $this->buildHello($app, $namespace);
    }

    /**
     * 创建应用的欢迎页面
     * @access protected
     * @param string $app 目录
     * @param string $namespace 类库命名空间
     * @return void
     */
    protected function buildHello(string $app, string $namespace): void
    {
        $appPath   = $this->basePath . ($app ? $app . DIRECTORY_SEPARATOR : '');
        $suffix    = $this->app->config->get('route.controller_suffix') ? 'Controller' : '';
        $filename  = $appPath . 'controller' . DIRECTORY_SEPARATOR . 'Index' . $suffix . '.php';
        $namespace .= '\controller';
        if (!is_file($filename)) {
            $content = file_get_contents(__DIR__ . '\stubs\controller.stub');
            $content = str_replace(['{%namespace%}', '{%className%}', '{%title%}'], [$namespace, 'Index', $app], $content);
            $this->checkDirBuild(dirname($filename));
            file_put_contents($filename, $content);
        }
    }

    /**
     * 创建应用的公共文件
     * @access protected
     * @param string $app 目录
     * @param string $option 插件信息
     * @return void
     */
    protected function buildCommon(string $app, array $option = []): void
    {
        $appPath             = $this->basePath . ($app ? $app . DIRECTORY_SEPARATOR : '');
        $option['namespace'] = 'addons' . ($app ? '\\' . $app : '');
        if (!is_file($appPath . 'Plugin.php')) {
            $path    = __DIR__ . '\stubs\Plugin.stub';
            $content = file_get_contents($path);
            foreach ($option as $key => $value) {
                $content = str_replace("{%" . $key . "%}", $value, $content);
            }
            file_put_contents($appPath . 'Plugin.php', $content);
        }
        if (!is_file($appPath . 'config.php')) {
            file_put_contents($appPath . 'config.php', "<?php" . PHP_EOL . "return [];" . PHP_EOL);
        }
    }

    /**
     * 创建目录
     * @access protected
     * @param string $dirname 目录名称
     * @return void
     */
    protected function checkDirBuild(string $dirname): void
    {
        if (!is_dir($dirname)) {
            mkdir($dirname, 0755, true);
        }
    }
}