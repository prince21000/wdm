define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'ad/ad/index' + location.search,
                    add_url: 'ad/ad/add',
                    edit_url: 'ad/ad/edit',
                    del_url: 'ad/ad/del',
                    multi_url: 'ad/ad/multi',
                    import_url: 'ad/ad/import',
                    table: 'ad',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title'), operate: 'LIKE'},
                        {
                            field: 'image',
                            title: __('Image'),
                            operate: false,
                            events: Table.api.events.image,
                            formatter: Table.api.formatter.image
                        },
                        {
                            field: 'type',
                            title: __('Type'),
                            searchList: {"1": __('Type 1'), "2": __('Type 2'), "3": __('Type 3'), "4": __('Type 4'), "5": __('Type 5')},
                            formatter: Table.api.formatter.normal
                        },
                        {field: 'path', title: __('Path'), operate: 'LIKE'},
                        {
                            field: 'status',
                            title: __('Status'),
                            searchList: {"1": __('Status 1'), "2": __('Status 2')},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'createtime',
                            title: __('Createtime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'updatetime',
                            title: __('Updatetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime
                        },
                        {field: 'category.name', title: __('Category.name'), operate: 'LIKE'},
                        {field: 'school.name', title: __('School.name'), operate: 'LIKE'},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'ad/ad/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title'), align: 'left'},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '140px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'ad/ad/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'ad/ad/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                /**
                 * 监听类型切换
                 */
                $(document).on("change", "#c-type", function () {
                    var type = $(this).val();
                    if (type == 1) {
                        $("#content-box").removeClass("hide");
                        $("#path-box").removeClass("hide");
                        $("#detail_id-box").removeClass("hide");
                        $("#exam_type_id-box").removeClass("hide");
                        $("#detail_id2-box").removeClass("hide");
                    } else if (type == 2) {
                        $("#content-box").removeClass("hide");
                        $("#path-box").addClass("hide");
                        $("#detail_id-box").addClass("hide");
                        $("#exam_type_id-box").addClass("hide");
                        $("#detail_id2-box").addClass("hide");
                    } else if (type == 3) {
                        $("#path-box").removeClass("hide");
                        $("#content-box").addClass("hide");
                        $("#detail_id-box").addClass("hide");
                        $("#exam_type_id-box").addClass("hide");
                        $("#detail_id2-box").addClass("hide");
                    } else if(type == 4) {
                        $("#exam_type_id-box").removeClass("hide");
                        $("#detail_id2-box").removeClass("hide");
                        $("#content-box").addClass("hide");
                        $("#path-box").addClass("hide");
                        $("#detail_id-box").addClass("hide");
                    } else {
                        $("#detail_id-box").removeClass("hide");
                        $("#content-box").addClass("hide");
                        $("#path-box").addClass("hide");
                        $("#exam_type_id-box").addClass("hide");
                        $("#detail_id2-box").addClass("hide");
                        $("#c-detail_id_text").data("selectPageObject").option.data = "school/school/index";
                    }
                });

                /**
                 * 监听备考类型切换
                 */
                $(document).on("change", "#c-exam_type_id", function () {
                    $("#c-category_id option[data-type='0']").prop("selected", true);
                    $("#c-category_id option").removeClass("hide");
                    $("#c-category_id option[data-type!='" + $(this).val() + "'][data-type!='0']").addClass("hide");
                    $("#c-category_id").data("selectpicker") && $("#c-category_id").selectpicker("refresh");
                });


                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
