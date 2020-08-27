class QueryBuilder:

    def __init__(self, escape_string_func=None):
        """
        escape_string_func :编码函数，如果是返回包含值的sql，建议传该函数，避免sql注入
        """
        self.__table_name = ''
        self.__select = None
        self.__join = []
        self.__insert = None
        self.__update = None
        self.__delete = None

        self.__duplicate_insert_data = None
        self.__duplicate_update_key = None
        self.__duplicate_const_data = None

        self.__where_list = []
        self.__group_by = ''
        self.__limit = 0
        self.__offset = 0

        self.__values = []  # 操作的值
        self.__raw_values = []

        self.__escape_string_fun = escape_string_func

    def reset(self):
        """
        重置查询参数
        """
        self.__table_name = ''
        self.__select = None
        self.__join = []
        self.__insert = None
        self.__update = None
        self.__delete = None

        self.__duplicate_insert_data = None
        self.__duplicate_update_key = None
        self.__duplicate_const_data = None

        self.__where_list = []
        self.__group_by = ''
        self.__limit = 0
        self.__offset = 0

        self.__values = []  # 操作的值
        self.__raw_values = []

    def table(self, table_name):
        """
        指定表名
        """
        self.__table_name = table_name
        return self

    def select(self, fields="*"):
        """
        执行查询字段，list，tuple，str
        list，tuple 会自动加反引号
        """
        if isinstance(fields, (list, tuple)):
            for field in fields:
                self.__select += "`%s`" % field
            return self
        if isinstance(fields, str):
            self.__select = fields
            return self
        return self

    def join(self, table, on):
        """
        join
        table:指定表名 b
        on：指定连接条件 a.c=b.d
        """
        self.__join.append((table, on))
        return self

    def insert(self, data):
        """
        data:dict,
        [dict,...]
        支持多次调用
        """
        if not self.__insert:
            self.__insert = []

        if isinstance(data, dict):
            self.__insert.append(data)
        elif isinstance(data, (list, tuple)):
            self.__insert += data
        return self

    def update(self, data):
        """
        支持多次调用，同一个key后面的会覆盖前者
        {'ab': 'b', 'c': 1, 'cc': ['cc', '+', 1]}

        SET `ab` = 'b',`c` = 1,`cc` = `cc` + 1
        """
        if not self.__update:
            self.__update = data
        else:
            self.__update.update(data)
        return self

    def insert_or_update(self, insert_data, update_key=True, const_data=None):
        """
        支持多次调用，如果指定update 则 以最后一次指定的为准
        insert_data:要插入的值
        update_key: True 将insert_data的key全部用于更新，False，不更新，list 指定要更新的key，
        const_data: dict 指定额外要更新的常量，格式和update的参数相同

        插入或者更新：insert into on duplicate key update
        insert into topic(topicName) VALUES ("a") ON DUPLICATE KEY UPDATE topicName=values(topicName)
        """
        if not self.__duplicate_insert_data:
            self.__duplicate_insert_data = []

        if isinstance(insert_data, dict):
            self.__duplicate_insert_data.append(insert_data)
        else:
            self.__duplicate_insert_data += insert_data

        self.__duplicate_update_key = update_key

        if not self.__duplicate_const_data:
            self.__duplicate_const_data = const_data
        else:
            self.__duplicate_const_data.update(const_data)

        return self

    def delete(self):
        self.__delete = True
        return self

    def where(self, where):
        """
        与前面的条件用and拼接
        1. dict
        {
            'a':'b', # a = b  默认用 = 连接
            'b':['>',1], # b > 1, 或者是元组
            'bbb': ['between', (1, 2)], # 指定between条件
        }

        2. 传list 或 tuple
        [['field','op','val'],['a','=','b'],['bb','between',(1,2)]


        a = 1 and c = 1 or d = 3
        like,=,!=,>,in,between
        and
        第一种形式：key = val and key = val
        {
            a:1,
            b:2,
        }
        第二种形式， filed op val and filed op val
        op: = ,>= ,< <= , like,not like
        in , between, not in,not between,
        [['filed','op','val']]，或者元组
        """
        self.__append_where_list(where, 'and')

        return self

    def where_or(self, or_where):
        """
        同where，与前面的条件用or拼接
        """
        self.__append_where_list(or_where, 'or')
        return self

    def where_in(self, field, in_data):
        """
        指定in条件，与前面的条件用and拼接
        """
        if not in_data:
            return self
        self.__append_where_list(((field, 'in', in_data),), 'and')
        return self

    def where_or_in(self, field, in_data):
        if not in_data:
            return self
        self.__append_where_list(((field, 'in', in_data),), 'or')
        return self

    def where_not_in(self, field, in_data):
        if not in_data:
            return self
        self.__append_where_list(((field, 'not in', in_data),), 'and')
        return self

    def where_or_not_in(self, field, in_data):
        if not in_data:
            return self
        self.__append_where_list(((field, 'not in', in_data),), 'or')
        return self

    def where_like(self, field, like):
        self.__append_where_list(((field, 'like', like),), 'and')
        return self

    def where_or_like(self, field, like):
        self.__append_where_list(((field, 'like', like),), 'or')
        return self

    def where_not_like(self, field, like):
        self.__append_where_list(((field, 'not like', like),), 'and')
        return self

    def where_or_not_like(self, field, like):
        self.__append_where_list(((field, 'not like', like),), 'or')
        return self

    def where_between(self, field, between):
        pass

    def where_or_between(self, field, between):
        pass

    def where_not_between(self, field, between):
        pass

    def where_or_not_between(self, field, between):
        pass

    def where_null(self, field):
        pass

    def where_or_null(self, field):
        pass

    def where_not_null(self, field):
        pass

    def where_or_not_null(self, field):
        pass

    def where_raw(self, where_raw, values=None):
        """
        连接where字符串
        """
        self.__raw_values.append(values)
        self.__append_where_list(where_raw, 'and')
        return self

    def where_or_raw(self, where_raw, values=None):
        """
        连接where字符串
        """
        self.__raw_values.append(values)
        self.__append_where_list(where_raw, 'or')
        return self

    def get_where(self, return_val=False, format_val=False):
        """
        获取where条件字符串，包含占位符
        return_val : 是否返回占位符对应的值
        format_val : 是否返回格式化后的值
        """
        where_str = ''
        # item 可能值，and，or，list，tuple，str
        self.__where_list.append('and')

        index = -1
        raw_index = 0
        for item in self.__where_list:
            index += 1
            if item == 'or' or item == 'and':
                if where_str == '':
                    where_str += "("
                    continue

                where_str = where_str[:len(where_str) - 4]
                if index == len(self.__where_list) - 1:
                    # 最后一个不处理
                    pass
                else:
                    where_str += ' ) %s ( ' % item
                continue

            elif isinstance(item, str):
                # where 字符串
                where_str += ' %s and ' % item
                if isinstance(self.__raw_values[raw_index], (tuple, list)):
                    for raw_val in self.__raw_values[raw_index]:
                        self.__values.append(raw_val)
                raw_index += 1

            elif isinstance(item, (list, tuple)):
                op_field = "`%s`" % item[0]
                op = item[1].strip().upper()
                op_val = item[2]
                if op == 'IN' or op == 'NOT IN':
                    if not op_val or not (isinstance(op_val, (list, tuple))):
                        continue

                    self.__values.append(op_val)

                    where_str += " %s %s %s and " % (op_field, op, '%s')

                elif op == 'BETWEEN' or op == 'NOT BETWEEN':
                    if not (isinstance(op_val, (list, tuple))):
                        continue
                    where_str += " %s %s %s AND %s AND " % (op_field, op, op_val[0], op_val[1])

                else:
                    self.__values.append(op_val)
                    where_str += ' %s %s %s AND ' % (op_field, op, '%s')
                    continue
        where_str = where_str + " )"
        if return_val:
            if format_val:
                return where_str % (*self.__escape_values(),)
            return where_str, self.__values
        else:
            return where_str

    def group_by(self, group_by):
        self.__group_by = group_by
        return self

    def limit(self, limit=10):
        self.__limit = limit
        return self

    def offset(self, offset=0):
        self.__offset = offset
        return self

    def page(self, page_index, page_size):
        """
        指定分页查询
        """
        if page_index <= 0:
            page_index = 1
        if page_size <= 0:
            page_size = 10
        self.offset((page_index - 1) * page_size)
        self.limit(page_size)
        return self

    def prepare_sql(self):
        """
        返回占位符和值列表
        """
        ret = self.__concat_sql(), self.__values
        self.reset()
        return ret

    def sql(self):
        """
        返回完整sql，把值拼接在sql语句中
        """
        sql = self.__concat_sql()
        real_val = self.__escape_values()
        self.reset()
        return sql % (*real_val,)

    def __escape_values(self):
        """
        对所有值进行转义
        """
        real_val = []
        for val in self.__values:
            if isinstance(val, (list, tuple)):
                in_val = "("
                for in_item in val:
                    es_val = in_item
                    if isinstance(in_item, str):
                        es_val = self.__escape_string(in_item)

                    in_val += str(es_val) + ','
                in_val = in_val[:len(in_val) - 1] + ")"
                real_val.append(in_val)
            else:
                real_val.append(self.__escape_string(val))
        return real_val

    def __append_where_list(self, where, action):
        """
        将where参数插入到where队列
        """
        if not where or action not in ('and', 'or'):
            return self

        if isinstance(where, dict):
            self.__where_list.append(action)
            for field, val in where.items():
                if isinstance(val, (list, tuple)):
                    self.__where_list.append((field, val[0], val[1]))
                else:
                    self.__where_list.append((field, '=', val))
            return self

        elif isinstance(where, (list, tuple)):
            self.__where_list.append(action)
            for item in where:
                if len(item) == 3:
                    self.__where_list.append(item)
            return self
        elif isinstance(where, str):
            self.__where_list.append(action)
            self.__where_list.append(where)

        return self

    def __insert_values(self):
        """
        data字典或data list、tuple转成insert语法
        返回带占位符字符串
        """
        fields = ''
        values = ''
        if isinstance(self.__insert, (list, tuple)):
            has_field = False
            for val_item in self.__insert:
                val_str = '('
                for key, val in val_item.items():
                    if not has_field:
                        fields += '`%s`,' % key
                    val_str += '%s,'
                    self.__values.append(val)

                has_field = True
                val_str = val_str[0:len(val_str) - 1] + ')'
                values += val_str + ','

            values = values[0:len(values) - 1]

            fields = fields[:len(fields) - 1]

        return fields, values

    def __duplicate_update(self):
        # key=VALUES(key), a = c
        update = ''
        if self.__duplicate_update_key is True:
            # 所有insert data都用于更新
            for key in self.__duplicate_insert_data[0].keys():
                update += "`%s` = VALUES(`%s`)," % (key, key)
        elif isinstance(self.__duplicate_update_key, (list, tuple)):
            # 指定部分key用于更新
            for key in self.__duplicate_update_key:
                update += "`%s` = VALUES(`%s`)," % (key, key)

        if isinstance(self.__duplicate_const_data, dict):
            # 处理指定的常量更新
            update += self.__handle_update_set(self.__duplicate_const_data) + ','

        return update[:len(update) - 1]

    def __update_sets(self):
        """
        data字典转成update语法
        返回带占位符字符串
        """
        return self.__handle_update_set(self.__update)

    def __handle_update_set(self, update_data):
        # 处理update set 后面的拼接，返回拼接后的占位符并把值插入self.__values
        sets = ''
        for key, val in update_data.items():
            # a=b,c=d
            if isinstance(val, (list, tuple)):
                sets += "`%s` = `%s` %s " % (key, val[0], val[1]) + "%s,"
                self.__values.append(val[2])
            else:
                sets += "`%s` = " % key + "%s,"
                self.__values.append(val)

        return sets[:len(sets) - 1]

    def __join_list(self):
        join = ''
        for join_item in self.__join:
            join += "join %s on %s " % join_item
        return join

    def __concat_sql(self):
        """
        拼接sql，返回带占位符的sql拼接
        """
        if self.__select:
            # 查询语句
            sql = "SELECT %s FROM %s " % (self.__select, self.__table_name,)
            if self.__join:
                sql += "%s " % self.__join_list()
            if self.__where_list:
                sql += "WHERE %s " % self.get_where()
            if self.__group_by:
                sql += "GROUP BY %s " % self.__group_by
            if self.__limit:
                sql += "LIMIT %s " % self.__limit
            if self.__offset:
                sql += "OFFSET %s " % self.__offset

            return sql
        elif self.__insert:
            # 插入语句
            fields, values = self.__insert_values()
            sql = "INSERT IN TO `%s`(%s) VALUES %s" % (self.__table_name, fields, values)
            return sql
        elif self.__update:
            # 更新语句
            sql = "UPDATE `%s` SET %s " % (self.__table_name, self.__update_sets())
            if self.__where_list:
                sql += "WHERE %s " % self.get_where()
            if self.__limit:
                sql += "LIMIT %s " % self.__limit
            if self.__offset:
                sql += "OFFSET %s " % self.__offset
            return sql
        elif self.__delete:
            # 删除
            sql = "DELETE FROM `%s` " % (self.__table_name,)
            if self.__where_list:
                sql += "WHERE %s " % self.get_where()
            if self.__limit:
                sql += "LIMIT %s " % self.__limit
            if self.__offset:
                sql += "OFFSET %s " % self.__offset
            return sql
        elif self.__duplicate_insert_data:
            # 插入或更新
            self.__insert = self.__duplicate_insert_data
            fields, values = self.__insert_values()
            # ON DUPLICATE KEY UPDATE topicName=values(topicName)
            duplicate_update = self.__duplicate_update()
            if duplicate_update:
                sql = "INSERT IN TO `%s`(%s) VALUES %s ON DUPLICATE KEY UPDATE %s " % (
                    self.__table_name, fields, values, duplicate_update)
            else:
                sql = "INSERT IN TO `%s`(%s) VALUES %s " % (
                    self.__table_name, fields, values)

            if self.__limit:
                sql += "LIMIT %s " % self.__limit
            if self.__offset:
                sql += "OFFSET %s " % self.__offset
            return sql

    def __escape_string(self, val):
        """防止sql注入"""
        if isinstance(val, (int, float)):
            return val
        if callable(self.__escape_string_fun):
            return self.__escape_string_fun(val)
        return "'" + str(val) + "'"


if __name__ == "__main__":
    query = QueryBuilder().table("tab")

    q = query.where({'a': 'c', 'd': 'f', 'kk': ('>', 10), 'bbb': ['between', (1, 2)]}) \
        .where([('a', 'like', '%s'), ('c', '>', 10)]) \
        .where_or({'o1': 'jjj'}) \
        .where({'k': 'g'}) \
        .where_in('ii', (1, 'dd', 3,)) \
        .where_not_in('jj', (3, 5, 6,)) \
        .where_raw(*QueryBuilder().where({'jjj': 'myjjj'}).get_where(True)) \
        .where_raw('a=c and d=f or kg=g') \
        .where_raw("k=%s and j = %s and kk in %s", (1, 3, [3, 4, 5])) \
        .where({'k': 'j', 'jk': 'jj', "jj": "ccc"}) \
        .group_by('a desc,b asc') \
        .offset(11) \
        .limit(12)

    print(q.select('a,b,c').sql())
    # print(q.delete().sql())
    exit()

    # join

    query.select('a,b,c').join('c', 'a.c=c.d').where({'a': 'd'})

    print(query.sql())
    exit()

    # insert or update
    query.insert_or_update([{'a': 'b', 'c': 'd'}, {'a': 'b', 'c': 'd'}], True, {'c': ['kk', '+', 1]}) \
        .insert_or_update({'a': 'zzv', 'c': 'jj'}, True, {'jj': 'll'})

    print(query.sql())
    exit()

    # delete
    print(query.delete().prepare_sql())

    sql = query.where({'ab': 'd'}).update({'c': 'd'}).sql()
    print(sql)

    # update
    sql = query.update({'ab': 'b', 'c': 1, 'cc': ['cc', '+', 1]}).limit(10).sql()
    print(sql)

    exit()

    # insert
    # s = query.insert({'ab': 'c', 'de': 'f'}).sql()
    s = query.insert(({'ab': 'c', 'de': 'f'}, {'ab': 'c', 'de': 'f'},)).sql()

    print(s)
    exit()
    # print(QueryBuilder().where({'jjj': 'myjjj'}).get_where(True,True))
