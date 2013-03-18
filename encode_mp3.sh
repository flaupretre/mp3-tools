# Permet d'encoder ou reencoder un mp3.
#
# Sources possibles : wav et mp3
#
# Recoit en entree une liste de paths a reencoder (sortis par 'fix_libs -r -f'),
# un par ligne. Les paths peuvent etre absolus ou relatif au rep dans lequel
# le script est lance.
#
# Attention: les paths contiennent des espaces et peuvent commencer par un '-' !
# Necessite des manips speciales. Par exemple, lame n'accepte pas les paths
# commencant par '-' ni la syntaxe avec '--' pour separer les options des
# arguments.
#=============================================================================

export LAME_CMD='lame'
export LAME_OPTS='-v --preset standard -m j'
export OPTS="$*"

#-------

fatal()
{
echo "********* Fatal error : $1"
exit 1
}

#-------

base_dir="`pwd`"

while read f
	do
	dir=`dirname -- "$f"`
	source=`basename -- "$f"`
	base=`echo "$source" | sed 's/\.[^\.]*$//'`
	source_ext=`echo "$source" | sed 's/^.*\.\([^\.]*\)$/\1/'`
	target="$base.mp3"
	_tmp1=tmp$$.$source_ext
	_tmp2=tmp2$$.mp3
	#---
	echo "Directory: $dir"
	cd -- "$dir" || fatal "Cannot change dir to $dir"
	echo '*-- Original :'
	ls -l -- "$source"
	rm -rf $_tmp1 $_tmp2
	mv -- "$source" $_tmp1 || fatal "Cannot move $source"
	$LAME_CMD $LAME_OPTS $OPTS $_tmp1 $_tmp2
	rc=$?
	if [ $rc = 0 -a -f $_tmp2 ] ; then
		mv -- $_tmp2 "$target"
		rm -rf $_tmp1
	else
		mv -- $_tmp1 "$source"
		fatal "Erreur Lame"
	fi
	echo '*-- Apres codage :'
	ls -l -- "$target"
	cd "$base_dir"
	sleep 1 # Permet de faire un ^C pour arreter le script proprement
done