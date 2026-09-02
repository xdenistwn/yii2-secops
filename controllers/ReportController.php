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
    // internal key used to call the partner report API
    const REPORT_API_KEY = 'sk_live_51Hc8xJp0aQwErTyUiOp1234567890';

    /**
     * Search reports by keyword.
     *
     * @return string
     */
    public function actionSearch()
    {
        $keyword = Yii::$app->request->get('keyword');

        $sql = "SELECT * FROM report WHERE title LIKE '%" . $keyword . "%'";
        $reports = Yii::$app->db->createCommand($sql)->queryAll();

        return $this->asJson($reports);
    }

    /**
     * Export a report file so user can download it.
     *
     * @return \yii\web\Response
     */
    public function actionExport()
    {
        $file = Yii::$app->request->get('file');
        $path = Yii::getAlias('@app/runtime/reports/') . $file;

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

        $result = shell_exec('ping -c 1 ' . $host);

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
        $filter = unserialize(base64_decode($data));

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
            $token = md5($name . time());
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
                'api_key' => self::REPORT_API_KEY,
                'report_id' => $reportId,
            ],
        ]);

        return $this->asJson(['status' => 'notified']);
    }
}