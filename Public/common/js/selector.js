/**
 * @Author : qiaoweizhen <qiaoweizhen@gmail.com>
 * @Github : https://www.github.com/qiaoweizhen
 * @Date   : 2016/8/19
 */

//默认配置
var CONFIG = {
    province: '#province',
    city: '#city',
    area: '#area'
};

//省市区选择方法
function selector(userConfig,s1,s2,s3) {

    //加载用户配置
    if (userConfig) {
        CONFIG = userConfig;
    }

    //获取省市区对象
    var province_obj = $(CONFIG.province);
    var city_obj = $(CONFIG.city);
    var area_obj = $(CONFIG.area);
	

	s4=s1-1;
	s5=s2-1;
	s6=s3-1;
    //初始化省市区
	if(s1==0){
		province_obj.html('').append('<option value=""> 请选择省 </option>');
	}else{
		province_obj.html('').append('<option value="'+s1+'">'+DATA[s4].name+'</option>');
		
	}
	if(s2==0){
		city_obj.html('').append('<option value=""> 请选择市 </option>');
	}else{
		city_obj.html('').append('<option value="'+s2+'">'+DATA[s5].name+'</option>');
		
	}
	
	if(s3==0){
		area_obj.html('').append('<option value=""> 请选择区 </option>');
	}else{
		area_obj.html('').append('<option value="'+s3+'">'+DATA[s6].name+'</option>');
		
	}
    
    

    //初始化省份列表
    for (i in DATA) {
        if (DATA[i].pid == 0) {
            province_obj.append('<option value="' + DATA[i].id + '">' + DATA[i].name + '</option>');
        }
    }

    //省份切换事件
    $(CONFIG.province).on('change', function () {
        var cur_province_id = $(this).val();
        //将市、区初始化
        city_obj.html('').append('<option value=""> 请选择市 </option>');
        area_obj.html('').append('<option value=""> 请选择区 </option>');
        if (cur_province_id != '') {
            for (i in DATA) {
                if (DATA[i].pid == cur_province_id) {
                    city_obj.append('<option value="' + DATA[i].id + '">' + DATA[i].name + '</option>');
                }
            }
        }
    });

    //城市切换事件
    $(CONFIG.city).on('change', function () {
        var cur_city_id = $(this).val();
        //将区初始化
        area_obj.html('').append('<option value=""> 请选择区 </option>');
        if (cur_city_id != '') {
            for (i in DATA) {
                if (DATA[i].pid == cur_city_id) {
                    area_obj.append('<option value="' + DATA[i].id + '">' + DATA[i].name + '</option>');
                }
            }
        }
    });
}