# Image-Recognition-API

PHP Class Image Recognition using Camfind API

Support Ajax Functionality with File Image Upload

Example Image File Request using JQuery

*Form Element*
```html
<form id="upload" enctype="multipart/form-data">
    Select image to upload:
    <input type="file" name="file" id="file">
    <input type="submit" name="submit" value="Upload Photo" />
</form>
```


*JavaScript*

```javascript
$('#upload').on('submit',function(e){
      e.preventDefault();
      var url = 'http://raveteam.net/camfind/ajax_upload.php';
      $.ajax({
           url: url,
           type: 'POST',
           data: new FormData(this),
           contentType: false,
           crossDomain: true,
           cache: false,
           processData: false,
           dataType: 'json',
           success: function(data) {
              console.log(data);
           },
           error: function(data) {
              console.log('Error');
              console.log(data);
           }
      });
});
```
