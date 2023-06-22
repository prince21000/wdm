define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/goldcoin_log/index' + location.search,
                    add_url: 'user/goldcoin_log/add',
                    edit_url: 'user/goldcoin_log/edit',
                    del_url: 'user/goldcoin_log/del',
                    multi_url: 'user/goldcoin_log/multi',
                    import_url: 'user/goldcoin_log/import',
                    table: 'user_goldcoin_log',
                }
            });

            var table = $("#table");

            //顶部搜索栏用户筛选
            table.on('post-common-search.bs.table', function (event, table) {
                var form = $("form", table.$commonsearch);
                $("input[name='user.nickname']", form).addClass("selectpage").data("source", "user/user/index").data("primaryKey", "id").data("field", "nickname").data("orderBy", "id desc");
                Form.events.cxselect(form);
                Form.events.selectpage(form);
            });

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
                        {
                            field: 'type',
                            title: __('Type'),
                            searchList: {
                                "1": __('Type 1'),
                                "2": __('Type 2'),
                                "3": __('Type 3'),
                                "4": __('Type 4'),
                                "5": __('Type 5'),
                                "6": __('Type 6'),
                                "7": __('Type 7'),
                                "8": __('Type 8')
                            },
                            formatter: Table.api.formatter.normal
                        },
                        {field: 'user.nickname', title: __('User.nickname'), operate: 'LIKE'},
                        {field: 'goldcoin', title: __('Goldcoin')},
                        {field: 'before', title: __('Before')},
                        {field: 'after', title: __('After')},
                        {field: 'memo', title: __('Memo'), operate: 'LIKE'},
                        {
                            field: 'createtime',
                            title: __('Createtime'),
                            operate: 'RANGE',
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
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
