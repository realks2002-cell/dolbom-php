<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>푸시 알림 테스트 - Hangbok77</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Noto Sans KR', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto max-w-2xl px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">푸시 알림 테스트</h1>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">1. 디바이스 토큰 등록/조회</h2>
            <p class="text-sm text-gray-600 mb-4">
                매니저 앱에서 로그인하면 자동으로 토큰이 등록됩니다.<br>
                또는 아래에서 전화번호로 매니저를 찾아 토큰을 등록할 수 있습니다.
            </p>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">매니저 전화번호</label>
                    <input 
                        type="tel" 
                        id="managerPhone" 
                        class="w-full px-4 py-2 border rounded-lg" 
                        placeholder="01012345678 (숫자만 입력)"
                        pattern="[0-9]*"
                        inputmode="numeric">
                    <p class="mt-1 text-xs text-gray-500">숫자만 입력해주세요</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">디바이스 토큰 (선택사항)</label>
                    <textarea id="deviceToken" class="w-full px-4 py-2 border rounded-lg" rows="3" placeholder="FCM 디바이스 토큰 입력 (등록 시에만 필요)"></textarea>
                    <p class="mt-1 text-xs text-gray-500">토큰을 입력하면 등록/업데이트, 비워두면 조회만 합니다</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="checkManager()" class="flex-1 bg-gray-600 text-white py-2 rounded-lg hover:bg-gray-700">
                        매니저 조회
                    </button>
                    <button onclick="registerToken()" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                        토큰 등록/업데이트
                    </button>
                </div>
                <div id="managerInfo" class="hidden mt-4 p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-semibold mb-2">매니저 정보</h3>
                    <div id="managerInfoContent" class="text-sm"></div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">2. 푸시 알림 전송 테스트</h2>
            <div class="space-y-4">
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h3 class="font-semibold text-blue-800 mb-2">📢 모든 매니저에게 전송</h3>
                    <p class="text-sm text-blue-700 mb-3">등록된 모든 활성 매니저에게 일괄 전송합니다.</p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium mb-2">알림 제목</label>
                            <input type="text" id="allTitle" class="w-full px-4 py-2 border rounded-lg" value="새로운 서비스 요청이 등록되었습니다" placeholder="알림 제목">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">알림 내용</label>
                            <textarea id="allBody" class="w-full px-4 py-2 border rounded-lg" rows="2" placeholder="알림 내용">병원 동행 서비스 요청이 결제 완료되었습니다.</textarea>
                        </div>
                        <button onclick="sendPushToAllManagers()" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                            모든 매니저에게 전송
                        </button>
                    </div>
                </div>
                
                <div class="border-t pt-4">
                    <h3 class="font-semibold mb-2">개별 토큰 테스트</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">디바이스 토큰</label>
                            <textarea id="testToken" class="w-full px-4 py-2 border rounded-lg" rows="3" placeholder="테스트할 디바이스 토큰 입력"></textarea>
                            <p class="mt-1 text-xs text-gray-500">위에서 조회한 토큰을 복사해서 사용하세요</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">알림 제목</label>
                            <input type="text" id="testTitle" class="w-full px-4 py-2 border rounded-lg" value="새로운 서비스 요청이 등록되었습니다" placeholder="알림 제목">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">알림 내용</label>
                            <textarea id="testBody" class="w-full px-4 py-2 border rounded-lg" rows="2" placeholder="알림 내용">병원 동행 서비스 요청이 결제 완료되었습니다.</textarea>
                        </div>
                        <button onclick="sendTestPush()" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">
                            개별 토큰으로 전송
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="result" class="hidden bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">결과</h2>
            <pre id="resultText" class="bg-gray-100 p-4 rounded text-sm overflow-auto"></pre>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6">
            <h3 class="font-semibold text-yellow-800 mb-2">⚠️ 참고사항</h3>
            <ul class="text-sm text-yellow-700 space-y-1">
                <li>• 실제 디바이스 토큰은 하이브리드 앱(Capacitor)에서만 받을 수 있습니다.</li>
                <li>• 웹 브라우저에서는 FCM 토큰을 받으려면 Firebase SDK 설정이 필요합니다.</li>
                <li>• 테스트용으로는 매니저 앱에서 등록된 토큰을 사용하세요.</li>
                <li>• DB에서 토큰 확인: <code class="bg-yellow-100 px-1 rounded">SELECT m.name, m.phone, mdt.device_token FROM managers m LEFT JOIN manager_device_tokens mdt ON m.id = mdt.manager_id WHERE mdt.is_active = 1</code></li>
            </ul>
        </div>
    </div>
    
    <script>
        // 전화번호 입력 필터링 (숫자만)
        const managerPhoneInput = document.getElementById('managerPhone');
        if (managerPhoneInput) {
            managerPhoneInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
        
        // 매니저 조회
        async function checkManager() {
            const phone = document.getElementById('managerPhone').value.trim();
            
            if (!phone) {
                alert('전화번호를 입력해주세요.');
                return;
            }
            
            try {
                const response = await fetch('/api/test/register-token-by-phone', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        phone: phone
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const infoDiv = document.getElementById('managerInfo');
                    const contentDiv = document.getElementById('managerInfoContent');
                    
                    let html = `<p><strong>이름:</strong> ${data.manager.name}</p>`;
                    html += `<p><strong>전화번호:</strong> ${data.manager.phone}</p>`;
                    html += `<p><strong>매니저 ID:</strong> ${data.manager.id}</p>`;
                    
                    if (data.tokens && data.tokens.length > 0) {
                        html += `<div class="mt-3"><strong>등록된 토큰:</strong><ul class="mt-2 space-y-1">`;
                        data.tokens.forEach((token, idx) => {
                            const isActive = token.is_active ? '✓ 활성' : '✗ 비활성';
                            html += `<li class="text-xs">
                                <div class="p-2 bg-white rounded border">
                                    <div>${isActive} | 플랫폼: ${token.platform}</div>
                                    <div class="mt-1 break-all text-xs">${token.device_token}</div>
                                    <button onclick="copyToken('${token.device_token}')" class="mt-1 text-blue-600 hover:underline text-xs">복사</button>
                                </div>
                            </li>`;
                        });
                        html += `</ul></div>`;
                    } else {
                        html += `<p class="mt-2 text-gray-500 text-sm">등록된 토큰이 없습니다.</p>`;
                    }
                    
                    contentDiv.innerHTML = html;
                    infoDiv.classList.remove('hidden');
                } else {
                    alert('오류: ' + (data.error || '알 수 없는 오류'));
                }
            } catch (error) {
                alert('오류 발생: ' + error.message);
            }
        }
        
        // 토큰 등록/업데이트
        async function registerToken() {
            const phone = document.getElementById('managerPhone').value.trim();
            const deviceToken = document.getElementById('deviceToken').value.trim();
            
            if (!phone) {
                alert('전화번호를 입력해주세요.');
                return;
            }
            
            if (!deviceToken) {
                alert('디바이스 토큰을 입력해주세요.');
                return;
            }
            
            try {
                const response = await fetch('/api/test/register-token-by-phone', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        phone: phone,
                        device_token: deviceToken,
                        platform: 'android'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('토큰이 ' + (data.message.includes('업데이트') ? '업데이트' : '등록') + '되었습니다!');
                    // 조회 결과 업데이트
                    checkManager();
                } else {
                    alert('오류: ' + (data.error || '알 수 없는 오류'));
                }
            } catch (error) {
                alert('오류 발생: ' + error.message);
            }
        }
        
        // 토큰 복사 함수
        function copyToken(token) {
            navigator.clipboard.writeText(token).then(() => {
                alert('토큰이 클립보드에 복사되었습니다.');
                // 테스트 토큰 필드에 자동 입력
                document.getElementById('testToken').value = token;
            }).catch(() => {
                // 폴백
                const textarea = document.createElement('textarea');
                textarea.value = token;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('토큰이 클립보드에 복사되었습니다.');
                document.getElementById('testToken').value = token;
            });
        }
        
        // 모든 매니저에게 푸시 알림 전송
        async function sendPushToAllManagers() {
            const title = document.getElementById('allTitle').value.trim();
            const body = document.getElementById('allBody').value.trim();
            
            if (!title || !body) {
                alert('제목과 내용을 입력해주세요.');
                return;
            }
            
            if (!confirm('등록된 모든 활성 매니저에게 푸시 알림을 전송하시겠습니까?')) {
                return;
            }
            
            try {
                const response = await fetch('/api/test/send-push-to-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        title: title,
                        body: body,
                        data: {
                            type: 'test_notification'
                        }
                    })
                });
                
                const data = await response.json();
                
                document.getElementById('result').classList.remove('hidden');
                document.getElementById('resultText').textContent = JSON.stringify(data, null, 2);
                
                if (data.success) {
                    const totalSent = data.result?.total_sent || 0;
                    alert(`푸시 알림이 전송되었습니다!\n전송된 토큰 수: ${totalSent}개`);
                } else {
                    alert('전송 실패: ' + (data.error || '알 수 없는 오류'));
                }
            } catch (error) {
                document.getElementById('result').classList.remove('hidden');
                document.getElementById('resultText').textContent = '오류: ' + error.message;
                alert('오류 발생: ' + error.message);
            }
        }
        
        // 푸시 알림 전송
        async function sendTestPush() {
            const token = document.getElementById('testToken').value.trim();
            const title = document.getElementById('testTitle').value;
            const body = document.getElementById('testBody').value;
            
            if (!token) {
                alert('디바이스 토큰을 입력해주세요.');
                return;
            }
            
            try {
                const response = await fetch('/api/test/send-push', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        device_token: token,
                        title: title,
                        body: body
                    })
                });
                
                const data = await response.json();
                
                document.getElementById('result').classList.remove('hidden');
                document.getElementById('resultText').textContent = JSON.stringify(data, null, 2);
                
                if (data.success) {
                    alert('푸시 알림이 전송되었습니다!');
                } else {
                    alert('전송 실패: ' + (data.error || '알 수 없는 오류'));
                }
            } catch (error) {
                document.getElementById('result').classList.remove('hidden');
                document.getElementById('resultText').textContent = '오류: ' + error.message;
                alert('오류 발생: ' + error.message);
            }
        }
    </script>
</body>
</html>
