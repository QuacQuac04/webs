<?php
class MBBankAPI {
    private $sessionId = '5d2fce56-b4b7-456b-91a9-f7baa7d27c6e';
    private $deviceId = '3c2lx6wj-mbib-0000-0000-2025030716010517';
    private $accountNumber = '0383116204';
    private $refNo = 'DINHVANHIEU23062004-2025032012164538-81313';
    private $baseUrl = 'https://online.mbbank.com.vn/retail-web-transactionservice/';

    public function getTransactionHistory($fromDate, $toDate) {
        try {
            $curl = curl_init();
            
            $data = [
                'accountNo' => $this->accountNumber,
                'deviceIdCommon' => $this->deviceId,
                'fromDate' => $fromDate,
                'sessionId' => $this->sessionId,
                'refNo' => $this->refNo,
                'toDate' => $toDate,
                'type' => 'ACCOUNT'
            ];

            error_log("MBBank Request Data: " . json_encode($data));

            curl_setopt_array($curl, [
                CURLOPT_URL => $this->baseUrl . 'transaction/getTransactionAccountHistory',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Basic ' . base64_encode($this->sessionId . ':' . $this->deviceId)
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 10
            ]);

            $response = curl_exec($curl);
            error_log("MBBank Raw Response: " . $response);
            
            if (curl_errno($curl)) {
                error_log("Curl Error: " . curl_error($curl));
                return ['error' => true, 'message' => 'Lỗi kết nối'];
            }

            curl_close($curl);
            
            // Thử parse response
            $responseData = json_decode($response, true);
            if (!$responseData) {
                error_log("Failed to parse JSON response");
                return ['error' => true, 'message' => 'Invalid response'];
            }

            return $responseData;

        } catch (Exception $e) {
            error_log("MBBank API Error: " . $e->getMessage());
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    public function checkTransaction($transactionId, $amount) {
        try {
            error_log("Checking transaction: {$transactionId} for amount: {$amount}");
            
            // Kiểm tra trực tiếp trong database trước
            global $conn;
            $stmt = $conn->prepare("SELECT Status FROM purchases WHERE TransactionID = ? AND Amount = ?");
            $stmt->execute([$transactionId, $amount]);
            $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($purchase && $purchase['Status'] === 'completed') {
                return ['status' => true, 'message' => 'Giao dịch đã hoàn tất'];
            }

            // Nếu chưa completed, kiểm tra với MBBank
            $fromDate = date('d/m/Y', strtotime('-1 hour')); // Chỉ lấy giao dịch trong 1 giờ gần nhất
            $toDate = date('d/m/Y');

            $result = $this->getTransactionHistory($fromDate, $toDate);
            error_log("MBBank transaction check result: " . json_encode($result));

            if (isset($result['transactionHistoryList'])) {
                foreach ($result['transactionHistoryList'] as $transaction) {
                    error_log("Checking transaction: " . json_encode($transaction));
                    
                    // Kiểm tra theo số tiền và nội dung chuyển khoản
                    if (isset($transaction['creditAmount']) && 
                        $transaction['creditAmount'] == $amount &&
                        strpos($transaction['description'], $transactionId) !== false) {
                        
                        // Cập nhật trạng thái trong database
                        $stmt = $conn->prepare("UPDATE purchases SET Status = 'completed' WHERE TransactionID = ?");
                        $stmt->execute([$transactionId]);

                        return [
                            'status' => true,
                            'message' => 'Tìm thấy giao dịch',
                            'transaction' => $transaction
                        ];
                    }
                }
            }

            return [
                'status' => false,
                'message' => 'Đang chờ giao dịch...'
            ];

        } catch (Exception $e) {
            error_log("Check Transaction Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Đang kiểm tra giao dịch...'
            ];
        }
    }
} 