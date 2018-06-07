<?php

namespace App\Controller;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ChipController extends Controller
{
    /**
     * @Route("/", name="chip")
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        $constrains = [
            new NotBlank(['message' => 'Заполните поле.']),
            new Range(['min' => 1, 'minMessage' => 'Число должно быть больше 0', 'invalidMessage' => 'Поле должно быть целым числом'])
        ];

        $form = $this->createFormBuilder()
            ->add('fieldCount', TextType::class, [
                'constraints' => $constrains,
                'label' => 'Количество ячеек'
            ])
            ->add('chipCount', TextType::class, [
                'constraints' => $constrains,
                'label' => 'Количество фишек'
            ])
            ->add('send', SubmitType::class, ['label' => 'Получить файл'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $fieldCount = $data['fieldCount'] - 1;
            $chipCount = $data['chipCount'] - 1;
            $fileName = 'result.txt';

            if ($chipCount < $fieldCount) {
                $this->writeFile($fileName, $chipCount, $fieldCount);
                return $this->file($fileName);
            }

            $form->addError(new FormError('Фишек должно быть меньше чем ячеек'));
        }

        return $this->render('chip/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Генератор вариантов
     *
     * @param $m int число ячеек
     * @param $n int число фишек
     * @return \Generator
     */
    private function getVariant($m, $n) {
        $variant = range(0, $m);
        yield $variant;
        $k = $m;
        while ($k >= 0) {
            if ($variant[$m] == $n) {
                $k--;
            } else {
                $k = $m;
            }
            if ($k >= 0) {
                for ($i = $m; $i >= $k; $i--) {
                    $variant[$i] = $variant[$k] + $i - $k + 1;
                }
                yield $variant;
            }
        }
    }

    /**
     * Записать варианты в файл
     *
     * @param $name string название файла
     * @param $chipCount int кол-во фишек
     * @param $fieldCount int кол-во ячеек
     */
    private function writeFile($name, $chipCount, $fieldCount) {
        $file = fopen($name, 'w');
        fwrite($file, '                                                       ' . PHP_EOL . PHP_EOL . implode(' ', range(1, $fieldCount + 1)) . PHP_EOL);

        $varCount = 0;
        foreach ($this->getVariant($chipCount, $fieldCount) as $variation) {
            $varCount++;
            $data = array_fill(0, $fieldCount + 1, '_');
            foreach ($variation as $index) {
                $data[$index] = '$';
            }
            fwrite($file, implode(' ', $data) . PHP_EOL . PHP_EOL);
        }
        fclose($file);

        if ($varCount < 10) {
            $filesystem = new Filesystem();
            $filesystem->dumpFile($name, 'Менее 10 вариантов' . PHP_EOL);
        } else {
            $file = fopen('result.txt', 'r+');
            fwrite($file, 'Количество вариантов: ' . $varCount);
            fclose($file);
        }
    }
}
