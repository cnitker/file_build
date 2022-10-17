<?php

namespace cnitker\command;


use cnitker\HttpCode;
use think\console\command\Make;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Exception;
use think\helper\Str;

class fileBuild extends Make
{
    private $controller = 'controller';
    private $model = 'model';
    private $logic = 'logic';
    private $validate = 'validate';

    protected $appList = [];

    protected function configure()
    {
        $this->appList = config('file_build');
        // 指令配置
        $this->setName('make:file-build')
            ->addOption('namespace', 's', Option::VALUE_REQUIRED, '控制器所在命名空间')
            ->addOption("model", 'm', Option::VALUE_REQUIRED, '请输入数据库预设模型名称')
            ->addOption("replace", 'r', Option::VALUE_OPTIONAL, '覆盖已生成文件默认:false')
            ->setDescription('一键生成控制器,逻辑,模型,验证层文件，[ php think make:file-build --namespace app/admin/controller/content --model GoodsOrder ]');
    }

    protected function execute(Input $input, Output $output)
    {
        $namespace = $input->getOption('namespace');
        $modelName = $input->getOption('model');
        $replace = empty($input->getOption('replace')) ? false : true;

        if (empty($namespace) || empty($modelName)) {
            $output->warning("--namespace 命名空间与--model 模型名称不能为空");
            return;
        }

        $dirname = substr($namespace, strrpos($namespace, "controller") + 11) ? substr($namespace, strrpos($namespace, "controller") + 11) : '';
        $modelName = ucfirst($modelName);
        $controllerName = $namespace . DIRECTORY_SEPARATOR . $modelName . 'Controller.php';

        if (strpos($dirname, 'v1') !== false) {
            $dirname = str_replace(['v1/', 'v2/'], '', $dirname);
        }

        $logicName = 'app/common/logic/' . $dirname . DIRECTORY_SEPARATOR . $modelName . 'Logic.php';
        $validateName = 'app/common/validate/' . $dirname . DIRECTORY_SEPARATOR . $modelName . 'Validate.php';
        $modelName = 'app/common/model/' . $dirname . DIRECTORY_SEPARATOR . $modelName . '.php';
        try {
            $validateResult = $this->writeToFile($validateName, $this->validate, $replace);
            $modelResult = $this->writeToFile($modelName, $this->model, $replace);
            $logicResult = $this->writeToFile($logicName, $this->logic, $replace);
            $controllerResult = $this->writeToFile($controllerName, $this->controller, $replace);
        } catch (Exception $e) {
            $output->error($e->getMessage());
            return;
        }

        if ($controllerResult['code'] == HttpCode::FAILURE_CODE) {
            $output->highlight($controllerResult['message']);
        } else {
            $output->info($controllerResult['message']);
        }

        if ($modelResult['code'] == HttpCode::FAILURE_CODE) {
            $output->highlight($modelResult['message']);
        } else {
            $output->info($modelResult['message']);
        }

        if ($logicResult['code'] == HttpCode::FAILURE_CODE) {
            $output->highlight($logicResult['message']);
        } else {
            $output->info($logicResult['message']);
        }

        if ($validateResult['code'] == HttpCode::FAILURE_CODE) {
            $output->highlight($validateResult['message']);
        } else {
            $output->info($validateResult['message']);
        }

        $output->writeln("执行完成！");
    }

    private function writeToFile($filePath, $type, $replace = false): array
    {
        $realFilePath = rtrim(root_path($filePath), DIRECTORY_SEPARATOR);

        if (file_exists($realFilePath) && !$replace) {
            return compact_array(1001, "【{$filePath}】文件已存在！");
        }

        $file_info = pathinfo($filePath);

        $dirname = $file_info['dirname'];
        $filename = $file_info['filename'];

        $namespace = str_replace('/', '\\', $dirname);
        $template = $this->getStub();

        if (!is_dir(root_path($dirname))) {
            mkdir(root_path($dirname), 0775, true);
        }
        $template_file = $template[$type];
        $template_file_content = file_get_contents($template_file);

        if ($type == $this->validate) {
            $search = ['{%namespace%}', '{%className%}'];
            $replace = [$namespace, $filename];
            $template_data = str_replace($search, $replace, $template_file_content);
            file_put_contents($realFilePath, $template_data);
        }

        if ($type == $this->model) {
            $search = ['{%namespace%}', '{%className%}', '{%tableName%}'];
            $replace = [$namespace, $filename, Str::snake($filename)];
            $template_data = str_replace($search, $replace, $template_file_content);
            file_put_contents($realFilePath, $template_data);
        }

        if ($type == $this->logic) {
            $search = ['{%namespace%}', '{%className%}', '{%modelNamespace%}', '{%modelName%}'];
            $modelName = str_replace('Logic', '', $filename);
            $modelNamespace = str_replace("/", '\\', str_replace("logic", 'model', $dirname) . DIRECTORY_SEPARATOR . $modelName . ';');
            $replace = [$namespace, $filename, $modelNamespace, $modelName];
            $template_data = str_replace($search, $replace, $template_file_content);
            file_put_contents($realFilePath, $template_data);
        }

        if ($type == $this->controller) {
            $search = ['{%namespace%}', '{%className%}', '{%baseControllerNamespace%}', '{%baseController%}', '{%logicNamespace%}', '{%logicName%}'];
            $logiclName = str_replace('Controller', 'Logic', $filename);

            $appName = explode("/", $dirname)[1];
            if (!isset($this->appList[$appName])) {
                throw new Exception("应用类型未定义！");
            }

            $appDirName = str_replace(['v1/', 'v2/'], '', substr($dirname, strrpos($namespace, "controller") + 11));
            $logicNamespace = str_replace('/', "\\", 'app/common/logic/' . $appDirName . DIRECTORY_SEPARATOR . $logiclName);
            $baseController = $this->appList[$appName]['filename'];
            $baseControllerNamespace = $this->appList[$appName]['namespace'];
            $replace = [$namespace, $filename, $baseControllerNamespace, $baseController, $logicNamespace, $logiclName];
            $template_data = str_replace($search, $replace, $template_file_content);
            file_put_contents($realFilePath, $template_data);
        }
        return compact_array(1000, "【{$filePath}】生成成功！");
    }

    protected function getStub(): array
    {
        return [
            'controller' => str_replace('command'.DIRECTORY_SEPARATOR,'',__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'controller.stub'),
            'model' => str_replace('command'.DIRECTORY_SEPARATOR,'',__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'model.stub'),
            'logic' => str_replace('command'.DIRECTORY_SEPARATOR,'',__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'logic.stub'),
            'validate' => str_replace('command'.DIRECTORY_SEPARATOR,'',__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'validate.stub')
        ];
    }

}
