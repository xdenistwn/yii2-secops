<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use GuzzleHttp\Client;

/**
 * Handles simple report tools: search report by keyword, export report file,
 * check host status, and submit feedback about a report.
 */
class ReportController extends Controller
{
    /**
     * Search reports by keyword.
     *
     * @return string
     */
    public function actionSearch()
    {
        $keyword = Yii::$app->request->get('keyword');

        $sql = "SELECT * FROM report WHERE title LIKE :keyword";
        $reports = Yii::$app->db->createCommand($sql, [
            ':keyword' => '%' . $keyword . '%',
        ])->queryAll();

        return $this->asJson($reports);
    }

    /**
     * Export a report file so user can download it.
     *
     * @return \yii\web\Response
     */
    public function actionExport()
    {
        $file = basename((string) Yii::$app->request->get('file'));
        $baseDir = Yii::getAlias('@app/runtime/reports/');
        $path = realpath($baseDir . $file);

        if ($path === false || strpos($path, realpath($baseDir)) !== 0) {
            throw new \yii\web\NotFoundHttpException('Report file not found.');
        }

        $content = file_get_contents($path);

        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/plain');

        return $content;
    }

    /**
     * Check whether a report source host is reachable.
     *
     * @return string
     */
    public function actionPing()
    {
        $host = Yii::$app->request->get('host');

        if (!preg_match('/^[a-zA-Z0-9.-]+$/', (string) $host)) {
            throw new \yii\web\BadRequestHttpException('Invalid host.');
        }

        $result = shell_exec('ping -c 1 ' . escapeshellarg($host));

        return $this->asJson(['result' => $result]);
    }

    /**
     * Restore a saved report filter from a shared link.
     *
     * @return string
     */
    public function actionFilter()
    {
        $data = Yii::$app->request->get('data');
        $filter = json_decode(base64_decode($data), true);

        return $this->asJson(['filter' => $filter]);
    }

    /**
     * Show feedback form and store the submitted comment.
     *
     * @return string
     */
    public function actionFeedback()
    {
        $name = Yii::$app->request->get('name');
        $comment = Yii::$app->request->post('comment');

        if ($comment !== null) {
            $token = Yii::$app->security->generateRandomString(32);
            Yii::$app->session->setFlash('feedbackToken', $token);
        }

        return $this->render('feedback', [
            'name' => $name,
        ]);
    }

    /**
     * Notify the partner report API that a report was viewed.
     *
     * @return string
     */
    public function actionNotify()
    {
        $reportId = Yii::$app->request->get('id');

        $client = new Client();
        $client->request('POST', 'https://reports.example.com/api/notify', [
            'query' => [
                'api_key' => Yii::$app->params['reportApiKey'],
                'report_id' => $reportId,
            ],
        ]);

        return $this->asJson(['status' => 'notified']);
    }
}
