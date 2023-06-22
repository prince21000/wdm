define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'exam/question_category/index' + location.search,
                    add_url: 'exam/question_category/add',
                    edit_url: 'exam/question_category/edit',
                    del_url: 'exam/question_category/del',
                    multi_url: 'exam/question_category/multi',
                    import_url: 'exam/question_category/import',
                    dragsort_url: '',
                    table: 'exam_question_category',
                }
            });

            var table = $("#table");

            table.on('post-common-search.bs.table', function (event, table) {
                console.log(table);
                var form = $("form", table.$commonsearch);
                $("input[name='type.name']", form).addClass('selectpage').data("source", "exam/type/index").data("primaryKey", "id").data("field", "name").data("orderBy", "id asc");
                Form.events.cxselect(form);
                Form.events.selectpage(form);
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                // fixedColumns: true,
                // fixedRightNumber: 1,
                pagination: false,
                commonSearch: true,
                search: false,
                // searchFormVisible: true,
                searchFormTemplate: 'question_category_formtpl',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {
                            field: 'type.name',
                            title: __('Type.name'),
                            operate: 'LIKE',
                            // searchList: Config.typeListConfig,
                            formatter: Table.api.formatter.label
                        },
                        {
                            field: 'name',
                            title: __('Name'),
                            operate: 'LIKE',
                            align: 'left',
                            formatter: function (value, row, index) {
                                return value.toString().replace(/(&|&amp;)nbsp;/g, '&nbsp;');
                            }
                        },
                        {
                            field: 'level',
                            title: __('Level'),
                            searchList: {"1": __('Level 1'), "2": __('Level 2'), "3": __('Level 3')},
                            operate: false,
                            formatter: Table.api.formatter.normal
                        },
                        // {field: 'level',title: __('Level'),searchList: {"1": __('Level 1'), "2": __('Level 2'), "3": __('Level 3')},formatter: Table.api.formatter.normal},
                        // {
                        //     field: 'image',
                        //     title: __('Image'),
                        //     operate: false,
                        //     events: Table.api.events.image,
                        //     formatter: Table.api.formatter.image
                        // },
                        {field: 'weigh', title: __('Weigh'), operate: false},
                        // {field: 'description', title: __('Description'), operate: 'LIKE'},
                        {
                            field: 'status',
                            title: __('Status'),
                            operate: false,
                            searchList: {"1": __('Status 1'), "2": __('Status 2')},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'goldcoin', title: __('Goldcoin'), operate: false},
                        {field: 'question_totalnum', title: __('Question_totalnum'), operate: false},
                        {field: 'accuracy', title: __('Accuracy'), operate: false},
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
                            operate: false,
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime
                        },
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

            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).closest("ul").data("field");
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                console.log(field)
                options.pageNumber = 1;
                options.queryParams = function (params) {
                    var filter = {};
                    if (value !== '') {
                        filter[field] = value;
                    }
                    params.filter = JSON.stringify(filter);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
            });
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
                url: 'exam/question_category/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), align: 'left'},
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
                                    url: 'exam/question_category/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'exam/question_category/destroy',
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

            /**
             * 监听父类选择变化
             */
            $(document).on("change", "#c-pid", function () {
                var cur_level = $(this).children('option:selected').attr('data-level');
                console.log(cur_level);
                if (cur_level == 0) {
                    $("#goldcoin-box").show();
                    $("#accuracy-box").show();
                } else {
                    $("#goldcoin-box").hide();
                    $("#accuracy-box").hide();
                }
            });
        },
        edit: function () {
            Controller.api.bindevent();

            /**
             * 监听父类选择变化
             */
            $(document).on("change", "#c-pid", function () {
                var cur_level = $(this).children('option:selected').attr('data-level');
                console.log(cur_level);
                if (cur_level == 0) {
                    $("#goldcoin-box").show();
                    $("#accuracy-box").show();
                } else {
                    $("#goldcoin-box").hide();
                    $("#accuracy-box").hide();
                }
            });
        },
        api: {
            bindevent: function () {
                $(document).on("change", "#c-exam_type_id", function () {
                    $("#c-pid option[data-type='0']").prop("selected", true);
                    $("#c-pid option").removeClass("hide");
                    $("#c-pid option[data-type!='" + $(this).val() + "'][data-type!='0']").addClass("hide");
                    $("#c-pid").data("selectpicker") && $("#c-pid").selectpicker("refresh");
                });

                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
