<article>
    <h3>배송시작으로 변경할까요?</h3>
    <div class="alert alert-danger">
        주의!
        택배 배송을 하지 않고 진행하는 거래 건이 허위 거래로 판정 된 경우 판매자 영구 정지 처리가 될 수 있음을 알립니다.
        또한 주문 취소를 요청하는 경우 판매자 지갑에서 상품 판매 금액의 5%를 차감하는 패널티가 있으니, 신중히 결정 후 배송을 진행 하십시오.
    </div>
    <div class="input-group mb-2 mr-sm-2">
        <div class="input-group-prepend">
            <div class="input-group-text">택배 회사 검색</div>
        </div>
        <input type="text" id="orderDeliveryCorpAutoComplete" class="form-control" placeholder="">
    </div>
    <div id="orderDeliveryCorpAutoCompleteArea" class="input-group mb-2 mr-sm-2">

    </div>
    <div class="input-group mb-2 mr-sm-2">
        <select class="custom-select" id="orderDeliveryCorp">
            <option value="" selected>배송방법을 선택해주세요.</option>
        </select>
    </div>
    <div class="input-group mb-2 mr-sm-2 d-none" id="orderDeliveryInvoiceArea">
        <div class="input-group-prepend">
            <div class="input-group-text" id="orderDeliveryInvoiceTitle"></div>
        </div>
        <input type="number" class="form-control" id="orderDeliveryInvoice" placeholder="">
    </div>
</article>
