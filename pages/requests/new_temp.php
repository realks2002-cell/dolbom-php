                <div>
                    <label for="guest_address" class="block text-sm font-medium text-gray-700">문자/알림 수신 주소</label>
                    <div class="mt-1 flex gap-2">
                        <input type="text" id="guest_address" name="guest_address" class="block flex-1 rounded-lg border border-gray-300 px-4 py-3" placeholder="도로명 또는 지번 주소 입력 후 검색" required autocomplete="off" aria-describedby="guest-address-search-msg">
                        <button type="button" id="btn-guest-address-search" class="shrink-0 min-h-[44px] min-w-[44px] rounded-lg bg-primary px-4 py-3 font-medium text-white hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" disabled>주소 검색</button>
                    </div>
                    <p id="guest-address-search-msg" class="mt-1 text-sm" role="status" aria-live="polite"></p>
                    <div id="guest-address-results" class="mt-2 hidden space-y-1" role="list" aria-label="주소 검색 결과"></div>
                    <div>
                        <label for="guest_address_detail" class="block text-sm font-medium text-gray-700">상세 주소 <span class="text-gray-400">(선택)</span></label>
                        <input type="text" id="guest_address_detail" name="guest_address_detail" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" placeholder="동/호수 등">
                    </div>
                </div>