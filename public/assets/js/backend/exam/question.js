define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'exam/question/index' + location.search,
                    add_url: 'exam/question/add',
                    edit_url: 'exam/question/edit',
                    del_url: 'exam/question/del',
                    multi_url: 'exam/question/multi',
                    import_url: 'exam/question/import',
                    table: 'exam_question',
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
                        {field: 'examtype.name', title: __('Examtype.name'), operate: 'LIKE'},
                        {field: 'topcate.name', title: __('Topcate.name'), operate: 'LIKE'},
                        {field: 'secondcate.name', title: __('Secondcate.name'), operate: 'LIKE'},
                        {field: 'thirdcate.name', title: __('Thirdcate.name'), operate: 'LIKE'},
                        {field: 'title', title: __('Title'), operate: 'LIKE'},
                        // {
                        //     field: 'option', title: __('Option'), operate: 'LIKE',
                        //     cellStyle: function (value, row, index, field) {
                        //         return {
                        //             css: {
                        //                 "white-space": "nowrap",//单行省略必备
                        //                 "text-overflow": "ellipsis",//单行省略必备
                        //                 "overflow": "hidden",//单行省略必备
                        //                 "color": "#3172a6",
                        //                 "width": "200px"
                        //             }
                        //         };
                        //     }
                        // },
                        {field: 'right_option', title: __('Right_option'), operate: 'LIKE'},
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
                        // {field: 'category.name', title: __('Category.name'), operate: 'LIKE'},
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
                url: 'exam/question/recyclebin' + location.search,
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
                                    url: 'exam/question/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'exam/question/destroy',
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
                 * 监听备考类型切换
                 */
                $(document).on("change", "#c-exam_type_id", function () {
                    $("#c-category_id option[data-type='0']").prop("selected", true);
                    $("#c-category_id option").removeClass("hide");
                    $("#c-category_id option[data-type!='" + $(this).val() + "'][data-type!='0']").addClass("hide");
                    $("#c-category_id").data("selectpicker") && $("#c-category_id").selectpicker("refresh");
                });

                // /**
                //  * 监听选项内容追加
                //  */
                // $(document).on("fa.event.appendfieldlist", "#option-table .btn-append", function (e, obj) {
                //     // console.log(e);
                //     // console.log(obj);
                //     $(".fieldlist input:first-child").trigger("change");
                //     var option_content = $("#option-content").val();
                //     Fast.api.ajax( {
                //         url: "exam/question/getOptionKey",
                //         data: {
                //             option_content: option_content
                //         }
                //     }, function (data, res) {
                //         console.log(res);
                //         if (res.code == 1) {
                //             $("#option-content").val(res.data);
                //             // var option_arr = JSON.parse(res.data);
                //             // option_arr.forEach(function (v, i) {
                //             //     $("input[name='row[option][" + i + "][option]']").val(v.option);
                //             // });
                //             Form.events.fieldlist($("#option-table"));
                //             // Form.events.fieldlist($("form"));
                //             $("#option-content").trigger("fa.event.refreshfieldlist", true);
                //         }
                //         return false;
                //     });
                // });
                //
                // /**
                //  * 监听选项内容删除
                //  */
                // $("#option-table").on("click", ".btn-remove", function (e, obj) {
                //     // console.log(e);
                //     // console.log(obj);
                //     console.log("执行到删除");
                //     var option_content = $("#option-content").val();
                //     console.log(option_content);
                //     return;
                //     $.post("exam/question/getOptionKey", {
                //         option_content: option_content
                //     }, function (res) {
                //         console.log(res);
                //         if (res.code == 1) {
                //             $("#option-content").val(res.data);
                //             var option_arr = JSON.parse(res.data);
                //             option_arr.forEach(function (v, i) {
                //                 $("input[name='row[option][" + i + "][option]']").val(v.option);
                //             });
                //             // // Form.events.fieldlist("#fieldlist-box");
                //             // $("#option-content").trigger("fa.event.refreshfieldlist", true);
                //         }
                //     });
                // });

                /**
                 * 监听是否答案选项
                 */
                $(document).on("change", "#option-table .option-state", function () {
                    //开关切换后的回调事件
                    var cur_val = $(this).val();
                    if (cur_val == 1) {
                        console.log("执行到change");
                        $("#option-table .option-state").not(this).val(0);
                        $("#option-table .option-state").not(this).next('a').children('i').addClass('fa-flip-horizontal text-gray');
                    }
                });

                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
