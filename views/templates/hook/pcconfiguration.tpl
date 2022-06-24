<div class="product-change" id='product-change'>

</div>
<div class="product-add-to-cart js-product-add-to-cart" style="float: left">
    <div class="product-quantity d-flex" style="margin-top: 10px;">
        <div class="qty mr-1">
                <div class="input-group bootstrap-touchspin bootstrap-touchspin-injected"><span class="input-group-btn input-group-prepend"><button class="btn btn-touchspin js-touchspin bootstrap-touchspin-down" type="button">-</button></span><input onChange="handleChangeQuantity(event)" style="height: 48px;" type="number" name="qty" id="quantity_wanted" inputmode="numeric" pattern="[0-9]*" value="1" min="1" max="10" class="input-group input-touchspin form-control" aria-label="Ilość"><span class="input-group-btn input-group-append"><button class="btn btn-touchspin js-touchspin bootstrap-touchspin-up" type="button">+</button></span></div>
        </div>
        <button
          class="btn btn-primary add-to-cart"
          type="button"
          id="add-to-cart"
          onClick="addToCart()"
        >
          {l s='Add to cart' d='Shop.Theme.Actions'}
        </button>


          </div>
          {include file="./modalAddToCart.tpl" }
    </div>
</div>

              <div>
                <span class="price price--lg" data-netto="{$product.price_tax_exc}" id="new-price-value" data-attribute="{$product->id_product_attribute}"></span>
              </div>
              <span style="margin-left: 10px;">BRUTTO</span>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    callAjax()
      prestashop.on(
      'updatedProduct',
      function (event) {
        callAjax()
      }
    );
  }, false);

  function handleChangeQuantity(event) {
    let value = event.target.value

    let max = getMaxQuantity();
    if (value > max) {
      $('#quantity_wanted').val(max);
    }
  }


  function getMaxQuantity() {
    let basicQuantity = parseInt($('#product_quantity').val())

    let quantities = [];
    quantities.push(basicQuantity)

    let elements = $('.selectedOption')

    for (let i = 0; i < elements.length; i++) {
        let elementId = elements[i].id;
        let quantity = parseInt($('#' + elementId).attr('data-quantity'))

        quantities.push(quantity)
    }

    let max =  Math.min.apply(Math, quantities);

    return max;
  }

  function callAjax() {
      let select = $('#group_{$attributeGroupId}');
      attr = select.val();
      let categoryIds = '{$categoryIds}';

      $.ajax({
        type: 'GET',
        data: {
          productId: {$product->id},
          group: attr,
          categoryIds: categoryIds,
          configurationId : {$configurationId}
        },
        url: '{Context::getContext()->link->getModuleLink("pcconfiguration","ajax")}',
        success: function(data) {
            let cats = data.cats;
            let fabric = data.fabric;
            let serviceFabric = data.serviceFabric;
            let servicePainting = data.servicePainting;
            reloadHook(cats, fabric, serviceFabric, servicePainting);
            
            setTimeout(function() {
              checkpack();
            }, 100)
        }
      });

      
  }

  function checkpack() {
      let attribute = $('#current-price-value').data('attribute')
      let productsInfo = attribute + '-' + '{$product->id}';

      let elements = $('.selectedOption.prod_to_pack');

      for (let i = 0; i < elements.length; i++) {
        let elementId = elements[i].id;
        if ($('#' + elementId).hasClass('prod_to_pack')) {
          let attributeId = $('#' + elementId).data('attribute')
          elementId = elementId.split('_')[3];
          if (attributeId != undefined) {
            productsInfo = productsInfo + '/' + attributeId + '-' + elementId;
          }
        } 
      }

       $.ajax({
        type: 'GET',
        data: {
          productsInfo: productsInfo,
        },
        url: '{Context::getContext()->link->getModuleLink("pcconfiguration","checkpack")}',
        success: function(data) {
            if (data.cover) {
              $('#image_cover').attr('src', data.cover);
            }
        }
      });
  }

  function reloadHook(cats, fabric, serviceFabric, servicePainting) {
    let basicQuantity = parseInt($('#product_quantity').val())

    let disable = basicQuantity < 1;
    let addToCart = $('#add-to-cart').prop('disabled', disable)

    let div = $('#product-change');
    let content = '';
    let sizeCat = Object.keys(cats).length;
    let elements = $('.selectedOption')
    let ids = []
    for (let i = 0; i < elements.length; i++) {
      ids.push(elements[i].id);
    }
  
    Object.keys(cats).map(function(key, index) {
        if (cats[key]['products'].length > 0) {
          if (cats[key]['type'] == 1) {
            content += '<div class="product-variants-item">' + '<p class="control-label h6 mb-2">' + cats[key]['name'] + '    <a class="question-mark" href="#" id="span_' + cats[key]['id_category'] + '" onmouseover="handleMouseOver(`span_' + cats[key]['id_category'] + '`)" data-content="' + cats[key]['description'] + '" rel="popover" data-placement="right" data-trigger="hover">?</a></p>' + '</div>';
            $('#span_' + cats[key]['id_category']).popover();
            content += '<select class="custom-select" id="cat_' + cats[key]['id_category'] + '" onChange="handleChangeProd(event, ' + cats[key]['id_category'] +')">'
            content += '<option value="0" id="cat_' + cats[key]['id_category'] + '_prod_0" class="cat_' + cats[key]['id_category'] + '" data-price="0">Brak wyboru</button></option>'
            Object.keys(cats[key]['products']).map(function(key2, index) {
              let addToPack =  cats[key]['add_to_package'] == 1 ? 'prod_to_pack' : ''
              content += '<option value="' + cats[key]['products'][key2]['id_product'] +'" class="' + addToPack + ' cat_' + cats[key]['id_category'] + '" data-netto="' + cats[key]['products'][key2]['netto'] + '" id="cat_' + cats[key]['id_category'] + '_prod_' + cats[key]['products'][key2]['id_product'] + '" data-quantity="' + cats[key]['products'][key2]['quantity'] + '" data-attribute="' + cats[key]['products'][key2]['id_attribute'] + '" data-price="' + cats[key]['products'][key2]['price'] + '"> ' + cats[key]['products'][key2]['name'] + '</option>'
            });
            content += '</select>'

            /////HARDCODE/////
            if (cats[key]['id_category'] == 5) {
              content += '<div id="pianka" >'
              content += '<div class="product-variants-item">' + '<p class="control-label h6 mb-2">' + 'Tkanina ' + '   <a class="question-mark" href="#" id="span_tkanina" onmouseover="handleMouseOver(`span_tkanina`)" data-content="Dostępne po wybraniu pianki samoklejącej jako tył ramy" rel="popover" data-placement="right" data-trigger="hover">?</a></p>' + '</div>';
              fabric.map((item) => {
                  content += '<img id="cat_9_prod_' + item['id_product'] +'" data-quantity="' + item['quantity'] + '" data-attribute="' + item['id_attribute'] + '" onClick="handleChangeFabric(' + item['id_product'] + ')" data-price="' + item['price'] + '" src="' + item['image'] + '" class="cat_9 fabric-image disabled-image"/>'
              })
              content += '</div>'
              content += '<div class="custom-control custom-switch">'
              content += '<input type="checkbox" class="custom-control-input" id="fabricSwitch" disabled onChange="handleChangeFabricSwitch(event)">'
              content +=  '<label class="custom-control-label" for="fabricSwitch">Dodaj usługę nakładania tkaniny</label>'
              content += '</div>'
              content += '<div class="service" id="cat_12_prod_' + serviceFabric['id'] + '" data-price="' + serviceFabric['price'] + '"></div>'

            }
            /////ENDHARDCODE/////
          } else if (cats[key]['type'] == 2) {      
            content += '<div class="product-variants-item">' + '<p class="control-label h6 mb-2">' + cats[key]['name'] + '   <a class="question-mark" href="#" id="span_' + cats[key]['id_category'] + '" onmouseover="handleMouseOver(`span_' + cats[key]['id_category'] + '`)" data-content="' + cats[key]['description'] + '" rel="popover" data-placement="right" data-trigger="hover">?</a></p>' + '</div>';
            Object.keys(cats[key]['products']).map(function(key2, index) {
                let addToPack =  cats[key]['add_to_package'] == 1 ? 'prod_to_pack' : ''

                content += '<img src="' + cats[key]['products'][key2]['image'] + '" data-netto="' + cats[key]['products'][key2]['netto'] + '" class="list_type ' + addToPack + ' cat_' + cats[key]['id_category'] + '" id="cat_' + cats[key]['id_category'] + '_prod_' + cats[key]['products'][key2]['id_product'] + '" onClick="handleChangeListType(' + cats[key]['products'][key2]['id_product'] + ', ' + cats[key]['id_category'] + ')" data-quantity="' + cats[key]['products'][key2]['quantity'] + '" data-attribute="' + cats[key]['products'][key2]['id_attribute'] + '" data-price="' + cats[key]['products'][key2]['price'] + '"/>'
            });
          } else if (cats[key]['type'] == 3) {
                Object.keys(cats[key]['products']).map(function(key2, index) {
                let addToPack =  cats[key]['add_to_package'] == 1 ? 'prod_to_pack' : ''

                content += '<div class="hidden_type selectedOption ' + addToPack + ' cat_' + cats[key]['id_category'] + '"  data-netto="' + cats[key]['products'][key2]['netto'] + '" id="cat_' + cats[key]['id_category'] + '_prod_' + cats[key]['products'][key2]['id_product'] + '" data-quantity="' + cats[key]['products'][key2]['quantity'] + '" data-attribute="' + cats[key]['products'][key2]['id_attribute'] + '" data-price="' + cats[key]['products'][key2]['price'] + '"></div>'
            });
          } else if (cats[key]['type'] == 4) {
            content += '<div class="product-variants-item">' + '<p class="control-label h6 mb-2">' + cats[key]['name'] + '   <a class="question-mark" href="#" id="span_' + cats[key]['id_category'] + '" onmouseover="handleMouseOver(`span_' + cats[key]['id_category'] + '`)" data-content="' + cats[key]['description'] + '" rel="popover" data-placement="right" data-trigger="hover">?</a></p>' + '</div>';
            Object.keys(cats[key]['products']).map(function(key2, index) {
                let addToPack =  cats[key]['add_to_package'] == 1 ? 'prod_to_pack' : ''

                content += '<img src="' + cats[key]['products'][key2]['image_big'] + '" data-netto="' + cats[key]['products'][key2]['netto'] + '" class="big_list_type ' + addToPack + ' cat_' + cats[key]['id_category'] + '" id="cat_' + cats[key]['id_category'] + '_prod_' + cats[key]['products'][key2]['id_product'] + '" onClick="handleChangeListType(' + cats[key]['products'][key2]['id_product'] + ', ' + cats[key]['id_category'] + ')" data-quantity="' + cats[key]['products'][key2]['quantity'] + '" data-attribute="' + cats[key]['products'][key2]['id_attribute'] + '" data-price="' + cats[key]['products'][key2]['price'] + '"/>'
            });
          }

          /////HARDCODE/////
          if (cats[key]['id_category'] == 6) {
              content += '<div class="custom-control custom-switch">'
              content += '<input type="checkbox" onChange="handleChangePaintingSwitch(event)" class="custom-control-input" id="paintingSwitch" disabled>'
              content += '<label class="custom-control-label" for="paintingSwitch">Dodaj usługę malowania ramy</label>'
              content += '</div>'
              content += '<div class="service" id="cat_12_prod_' + servicePainting['id'] + '" data-price="' + servicePainting['price'] + '"></div>'
          }
          /////ENDHARDCODE/////
        }
    });
            
    div.html(content);  

    for (let i = 0; i < ids.length; i++) {
      $('#' + ids[i]).addClass('selectedOption');
      $('#' + ids[i]).attr('selected', true);
      if ($('#' + ids[i]).hasClass('disabled-image')) {
        $('.fabric-image').removeClass('disabled-image');
      }
    }

    calculatePrice()
  }

  function handleMouseOver(id) {
    $('#' + id).popover();  
  }

  ////HARDCODE////
  function handleChangeFabricSwitch(event) {
   
      let product = $('#cat_12_prod_100')
      if (product.hasClass('selectedOption')) {
          product.removeClass('selectedOption');
      } else {
          product.addClass('selectedOption');
      }

      calculatePrice()
  }

  function handleChangePaintingSwitch(event) {
    let product = $('#cat_12_prod_99')
     if (product.hasClass('selectedOption')) {
        product.removeClass('selectedOption');
     } else {
        product.addClass('selectedOption');
     }

    calculatePrice()
  }
    ////ENDHARDCODE////

  function handleChangeFabric(idProd) {
    if (!$('#cat_9_prod_' + idProd).hasClass('disabled-image')) {
      $('#fabricSwitch').prop('disabled', false);

      if ($('#cat_9_prod_' + idProd).hasClass('selectedOption')) {
        $('.fabric-image').removeClass('selectedOption');
      } else {
        $('.fabric-image').removeClass('selectedOption');
        $('#cat_9_prod_' + idProd).addClass('selectedOption');
      }
    }
    calculatePrice()
  }

    function handleChangeListType(idProd, idCat) {
      if ($('#cat_' + idCat + '_prod_' + idProd).hasClass('selectedOption')) {
        $('.cat_' + idCat).removeClass('selectedOption');
      } else {
        $('.cat_' + idCat).removeClass('selectedOption');
        $('#cat_' + idCat + '_prod_' + idProd).addClass('selectedOption');
      }

      if (idCat == 6) {
        $('#paintingSwitch').prop('disabled', false);
      }

      calculatePrice()
    }

  function calculatePrice() {
    let newPrice = 0.0
    let elements = $('.selectedOption')
    for (let i = 0; i < elements.length; i++) {
      let elementId = elements[i].id;
      newPrice += parseFloat($('#' + elementId).attr('data-price'))
    }

    let symbol = '{Context::getContext()->currency->symbol}'
    let price = $('#current-price-value')
    let priceValue = price.attr('content')
    newPrice = parseFloat(priceValue) + parseFloat(newPrice);
    newPrice = newPrice.toFixed(2)
    newPrice = newPrice + ' ' + symbol;
    newPrice = newPrice.replace('.', ',')
    price.text(newPrice)
    let newPriceValue = $('#new-price-value')
    newPriceValue.text(newPrice)

    checkpack();
  }

  function handleChangeProd(event, idCat) {
    let idProd = event.target.value
    //////HARDCODE/////
    if (idCat == 5 && idProd != 59 && idProd != 138) {
      $('#fabricSwitch').prop('disabled', true);
      $('#fabricSwitch').prop( "checked", false );
      $('.fabric-image').addClass('disabled-image');
      $('.fabric-image').removeClass('selectedOption');
      $('#cat_12_prod_100').removeClass('selectedOption');
    } else if ( idProd == 59 || idProd == 138) {
      $('#fabricSwitch').prop('disabled', true);
      $('#fabricSwitch').prop( "checked", false );
      $('.fabric-image').removeClass('disabled-image');
      $('.fabric-image').removeClass('selectedOption');
      $('#cat_12_prod_100').removeClass('selectedOption');
    }
    /////ENDHARDCODE/////

    let id = event.target.id
    if ($( '#cat_' + idCat + '_prod_' + idProd ).hasClass( "selectedOption")) {
      $('.cat_' + idCat).removeClass('selectedOption');
    } else {
      $('.cat_' + idCat).removeClass('selectedOption');
      if (idProd != 0) {
        $('#cat_' + idCat + '_prod_' + idProd).addClass('selectedOption')
      }
    }

    calculatePrice()
  }
function addToCart() {
                
    let quantity = $('#quantity_wanted').val();
    let groupsId = "{$groupsId}"
    let groupArray = groupsId.split('-');

    let groupValues = ''
    groupArray.map((item) => {
      let value = $('#group_' + item).val()

      groupValues += '\'group[' + item + ']\': ' + value + ', '
    })
    let select = $('#group_{$attributeGroupId}');
    let attr = select.val();
    let labels = $('.product-change-label')
    let elements = $('.selectedOption').not('.service')
    let serviceElement = $('.selectedOption.service')

    for (let i = 0; i < serviceElement.length; i ++) {
      let element = $(serviceElement[i]);
      let elementId = element.prop('id')
      let prodId = elementId.split('_')[3];
      $.ajax({
        type: 'POST',
        data: {
          id_product: prodId,
          id_customization: 0,
          qty: quantity,
          add: 1,
          action: 'update',
        },
        url: '{$urls.pages.cart}',
      });
    }
      let attribute = $('#current-price-value').data('attribute')

      let productsInfo = attribute + '-' + '{$product->id}';
      let separateProducts = [];
      for (let i = 0; i < elements.length; i++) {
        let elementId = elements[i].id;
        let catId = elementId.split('_')[1];

        if ( $('#' + elementId).hasClass('prod_to_pack')) {
          let attributeId = $('#' + elementId).data('attribute')
          elementId = elementId.split('_')[3];
          if (attributeId != undefined) {
            productsInfo = productsInfo + '/' + attributeId + '-' + elementId;
          }
        } else {
          separateProducts.push(elementId);
        }
      }

      if (elements.length == 0 || separateProducts.length == elements.length) {
        let data =  {
            id_product: {$product->id},
            id_customization: 0,
            qty: quantity,
            add: 1,
            action: 'update',
        }

        groupArray.map((item) => {
          let value = $('#group_' + item).val()
          let name = 'group[' + item + ']'

          data[name] = value;
        })
        $.ajax({
          type: 'POST',
          data,
          url: '{$urls.pages.cart}',
          success: function(data) {
            separateProducts.forEach((item) => {
                      let element = $('#' + item);
                      let prodId = item.split('_')[3];
                      $.ajax({
                        type: 'POST',
                        data: {
                          id_product: prodId,
                          id_customization: 0,
                          'group[1]': attr, 
                          qty: quantity,
                          add: 1,
                          action: 'update',
                        },
                        url: '{$urls.pages.cart}',
                      });
                    })
            $('#largeModal').modal('show');
          }
        });
      } else {
          let newPrice = 0.0
          let elements = $('.selectedOption.prod_to_pack')
          for (let i = 0; i < elements.length; i++) {
            let elementId = elements[i].id;
            newPrice += parseFloat($('#' + elementId).data('netto'))
          }

          let price = $('#current-price-value')
          let priceValue = price.data('netto')

          newPrice = parseFloat(priceValue) + parseFloat(newPrice);
          newPrice = newPrice.toFixed(2)

        $.ajax({
            type: 'GET',
            data: {
              getProductId: true,
              productsInfo: productsInfo,
              price: newPrice,
            },
            url: '{Context::getContext()->link->getModuleLink("pcconfiguration","ajax")}',
            success: function(data) {
              if(data.productId != 'error') {
                $.ajax({
                  type: 'POST',
                  data: {
                    id_product: data.productId,
                    id_customization: 0,
                    qty: quantity,
                    add: 1,
                    action: 'update',
                  },
                  url: '{$urls.pages.cart}',
                  success: function(data) {       
                      separateProducts.forEach((item) => {
                      let element = $('#' + item);
                      let prodId = item.split('_')[3];
                      $.ajax({
                        type: 'POST',
                        data: {
                          id_product: prodId,
                          id_customization: 0,
                          'group[1]': attr, 
                          qty: quantity,
                          add: 1,
                          action: 'update',
                        },
                        url: '{$urls.pages.cart}',
                      });
                    })
                    $('#largeModal').modal('show');
                  }
                });
              }      
            }
          });
      }
}
</script>
<style>
  .otherProd {
    background-color: white;
    cursor: pointer;
  }
img.selectedOption {
  border: 2px solid red;
}
.btn-pc-configuration {
	padding: 5px;
    margin: 5px;
}

.disabled-image {
    opacity: 0.2;
    cursor: default !important;
}

.fabric-image {
  cursor: pointer;
  margin: 5px;
  border-radius: 100%;
  width: 50px;
}

.product-variants-item {
  margin-top: 10px;
} 

.list_type {
  cursor: pointer;
  margin: 5px;
  border-radius: 100%;
  width: 50px;
}

.big_list_type {
  cursor: pointer;
  margin: 5px;
}

.hidden_type {
  display: none;
}

.question-mark {
  color: #ba8c63;
  margin-left: 5px;
  padding: 5px;
}

@media only screen
and (min-width: 1200px) {
  .custom-switch {
      text-align: right;
      position: relative;
      top: -40px;
      margin-left: 50%;
  }
}

#new-price-value {
  margin-left: 10px;
}


</style>
